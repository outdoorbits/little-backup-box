import express from 'express';
import sqlite3 from 'sqlite3';
import { promisify } from 'util';
import path from 'path';
import { execCommand } from '../utils/execCommand.js';
import { readFileSync, existsSync } from 'fs';

const router = express.Router();

router.get('/init', async (req, res) => {
  try {
    const { mountpoint } = req.query;
    
    if (!mountpoint) {
      return res.status(400).json({ error: 'Mountpoint required' });
    }
    
    const command = `sudo python3 ${req.WORKING_DIR}/lib_view.py --action init --mountpoint ${mountpoint}`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) {
      return res.status(500).json({ error: 'Failed to initialize view' });
    }
    
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to initialize view', { error: error.message });
    res.status(500).json({ error: 'Failed to initialize view' });
  }
});

router.get('/images', async (req, res) => {
  try {
    const {
      storagePath,
      viewMode = 'grid',
      filterRating = -1,
      filterImagesPerPage = 50,
      selectOffset = 0,
      orderBy = 'ID',
      orderDir = 'ASC',
      gridColumns = 3,
    } = req.query;
    
    if (!storagePath) {
      return res.status(400).json({ error: 'Storage path required' });
    }
    
    const dbPath = path.join(storagePath, req.constants.const_IMAGE_DATABASE_FILENAME);
    
    if (!existsSync(dbPath)) {
      return res.json({ images: [], count: 0 });
    }
    
    const db = new sqlite3.Database(dbPath);
    const dbAll = promisify(db.all.bind(db));
    const dbGet = promisify(db.get.bind(db));
    
    let query = 'SELECT * FROM EXIF_DATA WHERE 1=1';
    const params = [];
    
    if (filterRating !== '-1' && filterRating !== -1) {
      query += ' AND LbbRating = ?';
      params.push(filterRating);
    }
    
    query += ` ORDER BY ${orderBy} ${orderDir}`;
    
    const allImages = await dbAll(query, params);
    const count = allImages.length;
    
    const images = allImages.slice(selectOffset, parseInt(selectOffset) + parseInt(filterImagesPerPage));
    
    db.close();
    
    res.json({ images, count });
  } catch (error) {
    req.logger.error('Failed to get images', { error: error.message });
    res.status(500).json({ error: 'Failed to get images' });
  }
});

router.post('/update-metadata', async (req, res) => {
  try {
    const { storagePath, imageId, comment, rating } = req.body;
    
    if (!storagePath || !imageId) {
      return res.status(400).json({ error: 'Storage path and image ID required' });
    }
    
    const dbPath = path.join(storagePath, req.constants.const_IMAGE_DATABASE_FILENAME);
    
    if (!existsSync(dbPath)) {
      return res.status(404).json({ error: 'Database not found' });
    }
    
    const db = new sqlite3.Database(dbPath);
    const dbGet = promisify(db.get.bind(db));
    const dbRun = promisify(db.run.bind(db));
    
    const image = await dbGet('SELECT * FROM EXIF_DATA WHERE ID = ?', [imageId]);
    
    if (!image) {
      db.close();
      return res.status(404).json({ error: 'Image not found' });
    }
    
    const imagePath = path.join(storagePath, image.Directory, image.File_Name);
    
    if (comment !== undefined) {
      const command = `sudo python3 ${req.WORKING_DIR}/lib_metadata.py '${imagePath}' --comment '${comment.replace(/'/g, "'\\''")}'`;
      await execCommand(command, { logger: req.logger });
    }
    
    if (rating !== undefined) {
      const command = `sudo python3 ${req.WORKING_DIR}/lib_metadata.py '${imagePath}' --rating '${rating}'`;
      await execCommand(command, { logger: req.logger });
    }
    
    if (comment !== undefined && rating !== undefined) {
      await dbRun(
        'UPDATE EXIF_DATA SET Comment = ?, LbbRating = ? WHERE ID = ?',
        [comment, rating, imageId]
      );
    } else if (comment !== undefined) {
      await dbRun('UPDATE EXIF_DATA SET Comment = ? WHERE ID = ?', [comment, imageId]);
    } else if (rating !== undefined) {
      await dbRun('UPDATE EXIF_DATA SET LbbRating = ? WHERE ID = ?', [rating, imageId]);
    }
    
    db.close();
    
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to update metadata', { error: error.message });
    res.status(500).json({ error: 'Failed to update metadata' });
  }
});

router.post('/delete-image', async (req, res) => {
  try {
    const { storagePath, imageId } = req.body;
    
    if (!storagePath || !imageId) {
      return res.status(400).json({ error: 'Storage path and image ID required' });
    }
    
    const dbPath = path.join(storagePath, req.constants.const_IMAGE_DATABASE_FILENAME);
    
    if (!existsSync(dbPath)) {
      return res.status(404).json({ error: 'Database not found' });
    }
    
    const db = new sqlite3.Database(dbPath);
    const dbGet = promisify(db.get.bind(db));
    const dbRun = promisify(db.run.bind(db));
    
    const image = await dbGet('SELECT * FROM EXIF_DATA WHERE ID = ?', [imageId]);
    
    if (!image) {
      db.close();
      return res.status(404).json({ error: 'Image not found' });
    }
    
    const imagePath = path.join(storagePath, image.Directory, image.File_Name);
    const baseName = path.basename(imagePath, path.extname(imagePath));
    const dir = path.dirname(imagePath);
    
    const deleteFile = imagePath;
    const deleteTims = path.join(dir, `${baseName}.tims`);
    const deleteXmp = path.join(dir, `${baseName}.xmp`);
    
    await execCommand(`sudo rm '${deleteFile}'`, { logger: req.logger });
    await execCommand(`sudo rm '${deleteTims}'`, { logger: req.logger });
    await execCommand(`sudo rm '${deleteXmp}'`, { logger: req.logger });
    
    await dbRun('DELETE FROM EXIF_DATA WHERE ID = ? AND LbbRating = -1', [imageId]);
    
    db.close();
    
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to delete image', { error: error.message });
    res.status(500).json({ error: 'Failed to delete image' });
  }
});

router.get('/stats', async (req, res) => {
  try {
    const { storagePath } = req.query;
    
    if (!storagePath) {
      return res.status(400).json({ error: 'Storage path required' });
    }
    
    const dbPath = path.join(storagePath, req.constants.const_IMAGE_DATABASE_FILENAME);
    
    if (!existsSync(dbPath)) {
      return res.json({
        imagesAll: 0,
        directories: [],
        ratings: [],
        dates: [],
        fileTypes: [],
        fileTypeExtensions: [],
        cameraModelNames: [],
      });
    }
    
    const db = new sqlite3.Database(dbPath);
    const dbAll = promisify(db.all.bind(db));
    
    const imagesAll = await dbAll('SELECT COUNT(*) as count FROM EXIF_DATA');
    const directories = await dbAll('SELECT DISTINCT Directory FROM EXIF_DATA');
    const ratings = await dbAll('SELECT LbbRating, COUNT(*) as count FROM EXIF_DATA GROUP BY LbbRating');
    const dates = await dbAll('SELECT DISTINCT Create_Date FROM EXIF_DATA WHERE Create_Date IS NOT NULL');
    const fileTypes = await dbAll('SELECT DISTINCT File_Type FROM EXIF_DATA');
    const fileTypeExtensions = await dbAll('SELECT DISTINCT File_Extension FROM EXIF_DATA');
    const cameraModelNames = await dbAll('SELECT DISTINCT Camera_Model_Name FROM EXIF_DATA WHERE Camera_Model_Name IS NOT NULL');
    
    db.close();
    
    res.json({
      imagesAll: imagesAll[0]?.count || 0,
      directories: directories.map(d => d.Directory),
      ratings,
      dates: dates.map(d => d.Create_Date),
      fileTypes: fileTypes.map(f => f.File_Type),
      fileTypeExtensions: fileTypeExtensions.map(f => f.File_Extension),
      cameraModelNames: cameraModelNames.map(c => c.Camera_Model_Name),
    });
  } catch (error) {
    req.logger.error('Failed to get stats', { error: error.message });
    res.status(500).json({ error: 'Failed to get stats' });
  }
});

export default router;


