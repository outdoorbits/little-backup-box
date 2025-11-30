// server.js
const express = require('express');
const path = require('path');
const app = express();

app.use((req, res, next) => {
  if (req.url.endsWith('.php')) {
    console.log('Rewriting php request');
//    req.url = req.url.replace(/\.php/, '.html');
    res.type('text/html; charset=utf-8');
  }
  next();
});

app.use(express.static(process.cwd()));

app.listen(8080, () => console.log('serving on http://localhost:8080'));

