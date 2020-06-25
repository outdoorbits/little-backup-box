// GPX2GM.js
// Darstellung von GPS-Daten aus einer GPX-Datei in Openstreetmap oder Google Maps  
// Lizenz CC BY-NC-SA 4.0
// Jürgen Berkemeier
// www.j-berkemeier.de
// Version 6.8 vom 29. 3. 2020

"use strict";

window.JB = window.JB || {};
window.JB.GPX2GM = window.JB.GPX2GM || {};
JB.GPX2GM.ver = "6.8";
JB.GPX2GM.dat = "29. 3. 2020";
JB.GPX2GM.fname = "GPX2GM.js";
JB.GPX2GM.globalMapParameter = {};

if(typeof(GPXVIEW_Debuginfo)=="undefined") 
	JB.debuginfo = (location.search.toLowerCase().search("debuginfo")!=-1) 
							&& (location.search.toLowerCase().search("debuginfo=false")==-1) ;    
else
	JB.debuginfo = GPXVIEW_Debuginfo;
if(JB.debuginfo) JB.gpxview_Start = Date.now();

(function() {
	JB.GPX2GM.Path = "";
	JB.GPX2GM.autoload = false;
	var scr = document.getElementsByTagName("script");
	for(var i=scr.length-1;i>=0;i--) if(scr[i].src && scr[i].src.length) {
		var path = scr[i].src;
		var pos = path.search(JB.GPX2GM.fname);
		if(pos!=-1) {
			JB.GPX2GM.autoload = !(path.search("autoload=false")>pos);
			JB.GPX2GM.Path = path.substring(0,pos);
			break;
		}
	}
})();

window.requestAnimationFrame = window.requestAnimationFrame || function(callback) { window.setTimeout(callback,1) };

JB.Scripte = { GPX2GM_Defs:0, maplib:0, gra:0, plot:0, maputils:0 };

JB.makeMap = function (ID) {

	JB.Debug_Info(ID,"makeMap gestartet",false);
	
	var dieses = this;
	var gpxdaten;
	var id = ID;
	this.id = id;
	var markers=[],trackpolylines=[],routepolylines=[];
	var file,maptype;
	var Map;
	var newfile;

	var div = document.getElementById(id);
	
	this.parameters = {};
	for(var par in JB.GPX2GM.parameters) this.parameters[par] = JB.GPX2GM.parameters[par];
	JB.Debug_Info(ID,"Parameter: " + JSON.stringify(div.dataset),false);
	for(var par in div.dataset) {
		if(par in this.parameters) {
			if(div.dataset[par] =="false") this.parameters[par] = false;
			else if(div.dataset[par] =="true") this.parameters[par] = true;
			else this.parameters[par] = div.dataset[par];
		}
		else {
			JB.Debug_Info(ID,"Unbekannter Parameter: " + par,true);
		}
	}
	if(JB.debuginfo) {
		var t = "";
		for(var o in this.parameters) t += "<br>&nbsp;&nbsp;" + o + ": " + this.parameters[o];
		JB.Debug_Info(ID,"Lokale Steuervariablen: "+t+"<br>",false);
	}
	
	var hscale=[],sscale=[],vscale=[],hrscale=[],cadscale=[],atempscale=[];
	if(typeof(JB.Scaling)!="undefined") {
		if(typeof(JB.Scaling.hmin)!="undefined" && typeof(JB.Scaling.hmax)!="undefined") 
			hscale = [{x:.0001,h:JB.Scaling.hmin} ,{x:.0002,h:JB.Scaling.hmax}] ;
		if(typeof(JB.Scaling.smin)!="undefined" && typeof(JB.Scaling.smax)!="undefined") 
			sscale = [{x:.0001,s:JB.Scaling.smin} ,{x:.0002,s:JB.Scaling.smax}] ;
		if(typeof(JB.Scaling.vmin)!="undefined" && typeof(JB.Scaling.vmax)!="undefined") 
			vscale = [{x:.0001,v:JB.Scaling.vmin} ,{x:.0002,v:JB.Scaling.vmax}] ;
		if(typeof(JB.Scaling.hrmin)!="undefined" && typeof(JB.Scaling.hrmax)!="undefined") 
			hrscale = [{x:.0001,hr:JB.Scaling.hrmin} ,{x:.0002,hr:JB.Scaling.hrmax}] ;
		if(typeof(JB.Scaling.cadmin)!="undefined" && typeof(JB.Scaling.cadmax)!="undefined") 
			cadscale = [{x:.0001,cad:JB.Scaling.cadmin} ,{x:.0002,cad:JB.Scaling.cadmax}] ;
		if(typeof(JB.Scaling.atempmin)!="undefined" && typeof(JB.Scaling.atempmax)!="undefined") 
			atempscale = [{x:.0001,atemp:JB.Scaling.atempmin} ,{x:.0002,atemp:JB.Scaling.atempmax}] ;
	}
	
	var doc_lang = JB.GPX2GM.parameters.doclang.toLowerCase();
	if(doc_lang == "auto" && document.documentElement.hasAttribute("lang")) doc_lang = document.documentElement.getAttribute("lang");
	if(doc_lang in JB.GPX2GM.strings) JB.GPX2GM.parameters.doclang = doc_lang;
	else                              JB.GPX2GM.parameters.doclang = doc_lang = "de";
	var strings = JB.GPX2GM.strings[doc_lang];
	var unit = this.parameters.unit.toLowerCase();
	if(unit == "airwater" || unit == "air" || unit == "water") {
		var units = JB.GPX2GM.units[unit];
		if(typeof(Wfaktor)=="undefined") this.parameters.wfaktor = 1/1.852;
		if(typeof(Hfaktor)=="undefined") this.parameters.hfaktor = 1/0.3048;
		if(typeof(Sfaktor)=="undefined") this.parameters.sfaktor = 0.3048 / 1.852;
	}
	else if(unit == "enus" || unit == "en" || unit == "us") {
		var units = JB.GPX2GM.units.enus;
		if(typeof(Wfaktor)=="undefined") this.parameters.wfaktor = 1/1.609344;
		if(typeof(Hfaktor)=="undefined") this.parameters.hfaktor = 1/0.3048;
		if(typeof(Sfaktor)=="undefined") this.parameters.sfaktor = 0.3048 / 1.609344;
	}
	else 
		var units = JB.GPX2GM.units.si;
	if(unit == "us") {
		if(typeof(Tfaktor)=="undefined") this.parameters.tfaktor = 9/5;
		if(typeof(Toffset)=="undefined") this.parameters.toffset = 32;
	}
	JB.Debug_Info(ID,"Sprache: "+doc_lang+" Einheiten: "+this.parameters.unit,false);

	if(typeof(JB.GPX2GM.callback)=="function") 
		JB.GPX2GM.callback({id:id,type:"Map_div_v"});

	JB.GPX2GM.globalMapParameter[id] = {keydownhandler: null};
	JB.GPX2GM.globalMapParameter[id] = {closeActivehandler: null};
	JB.GPX2GM.globalMapParameter.activeMapId = id;

	div.tabIndex = 0;
	div.addEventListener("focusin",function(e) { JB.GPX2GM.globalMapParameter.activeMapId = this.id; },false);
	div.addEventListener("mouseover",function(e) { JB.GPX2GM.globalMapParameter.activeMapId = this.id; },false);
	JB.addClass("JBmapdiv",div);

	var MapHead = document.createElement("div");
	MapHead.id = "map_head"+id;
	JB.addClass("JBmaphead",MapHead);
	MapHead.setAttribute("role","menu"); // oder menubar?
	MapHead.appendChild(document.createTextNode(": "));
	var mapdiv = document.createElement("div");
	this.mapdiv = mapdiv;
	mapdiv.id = "map_"+id;
	mapdiv.style.height = "100%";
	while(div.hasChildNodes()) div.removeChild(div.firstChild);
	if(!this.parameters.legende) MapHead.style.display = "none";
	var odiv = document.createElement("div");
	odiv.style.width = odiv.style.height = "100%";
	odiv.appendChild(MapHead);
	odiv.appendChild(mapdiv);
	div.appendChild(odiv);
	if (this.parameters.trcolmod.length) {
		try { mapdiv.style.width = "calc(100% - 90px)"; } catch(e) {}
		odiv.style.position = "relative";
		var FB;
		var fb_onresize;
	}
	if(typeof(JB.GPX2GM.callback)=="function") 
		JB.GPX2GM.callback({id:id,type:"Map_div_n"});
	JB.Debug_Info(ID,"Mapdiv angelegt "+mapdiv.offsetWidth+"*"+mapdiv.offsetHeight,false);

	this.parameters.profilflag = false;
	var profil = {
		hp:{x:"x",y:"h"},hpt:{x:"t",y:"h"},
		wpt:{x:"t",y:"x"},
		sp:{x:"x",y:"s"},spt:{x:"t",y:"s"},
		vp:{x:"x",y:"v"},vpt:{x:"t",y:"v"},
		hrp:{x:"x",y:"hr"},hrpt:{x:"t",y:"hr"},
		cadp:{x:"x",y:"cad"},cadpt:{x:"t",y:"cad"},
		atempp:{x:"x",y:"atemp"},atemppt:{x:"t",y:"atemp"}
	};
	if(this.parameters.shtrtabs_p) 
		profil.hpt.x = profil.spt.x = profil.vpt.x = profil.hrpt.x = profil.cadpt.x = profil.atemppt.x = profil.wpt.x = "tabs";
	profil.hpt.ytext = profil.hp.ytext = strings.alt+strings.in+units.alt;
	profil.spt.ytext = profil.sp.ytext = strings.grade+strings.in+strings.grade_unit;
	profil.vpt.ytext = profil.vp.ytext = strings.speed+strings.in+units.speed;
	profil.hrpt.ytext = profil.hrp.ytext = strings.hr+strings.in+strings.hr_unit;
	profil.cadpt.ytext = profil.cadp.ytext = strings.cad+strings.in+strings.cad_unit;
	profil.atemppt.ytext = profil.atempp.ytext = strings.temp+strings.in+units.temp;
	profil.wpt.ytext = strings.way+strings.in+units.way;
	profil.hp.xtext = profil.vp.xtext = profil.sp.xtext = profil.hrp.xtext = profil.cadp.xtext = profil.atempp.xtext = strings.way+strings.in+units.way;
	profil.hpt.xtext = profil.vpt.xtext = profil.spt.xtext = profil.hrpt.xtext = profil.cadpt.xtext = profil.atemppt.xtext = profil.wpt.xtext = strings.time; //+strings.in+strings.time_unit; 
	profil.hpt.scale = profil.hp.scale = hscale;
	profil.spt.scale = profil.sp.scale = sscale;
	profil.vpt.scale = profil.vp.scale = vscale;
	profil.hrpt.scale = profil.hrp.scale = hrscale;
	profil.cadpt.scale = profil.cadp.scale = cadscale;
	profil.atemppt.scale = profil.atempp.scale = atempscale;
	profil.setflags = function(tr,ct) {
		if(ct==-1) {
			profil.hp.pflag = profil.sp.pflag = tr.hflag;
			profil.hpt.pflag = profil.spt.pflag = tr.hflag && tr.tflag;
			profil.vpt.pflag = profil.vp.pflag = tr.tflag;
			profil.hrpt.pflag = profil.hrp.pflag = tr.hrflag;
			profil.hrpt.pflag &= tr.tflag;
			profil.cadpt.pflag = profil.cadp.pflag = tr.cadflag;
			profil.cadpt.pflag &= tr.tflag;
			profil.atemppt.pflag = profil.atempp.pflag = tr.atempflag;
			profil.atemppt.pflag &= tr.tflag;
			profil.wpt.pflag = tr.tflag;
		}
		else {
			profil.hp.pflag = profil.sp.pflag = ct==1?tr.hflag:tr.hflagall;
			profil.hpt.pflag = profil.spt.pflag = ct==1?tr.hflagall&&tr.tflag:tr.hflagall&&tr.tflagall;
			profil.vpt.pflag = profil.vp.pflag = ct==1?tr.tflag:tr.tflagall;
			profil.hrpt.pflag = profil.hrp.pflag = ct==1?tr.hrflag:tr.hrflagall;
			profil.hrpt.pflag &= ct==1?tr.tflag:tr.tflagall;
			profil.cadpt.pflag = profil.cadp.pflag = ct==1?tr.cadflag:tr.cadflagall;
			profil.cadpt.pflag &= ct==1?tr.tflag:tr.tflagall;
			profil.atemppt.pflag = profil.atempp.pflag = ct==1?tr.atempflag:tr.atempflagall;
			profil.atemppt.pflag &= ct==1?tr.tflag:tr.tflagall;
			profil.wpt.pflag = ct==1?tr.tflag:tr.tflagall;
		}
	}

	for(var p in profil) {
		profil[p].id = ID+"_"+p;
		profil[p].ele = document.getElementById(profil[p].id);
		if(profil[p].ele) {
			JB.addClass("JBprofildiv",profil[p].ele);
			this.parameters.profilflag = true;
			JB.Debug_Info(id,"Profil, ID: "+profil[p].id+" gefunden",false);
		}
	}

	if(this.parameters.profilflag || this.parameters.trcolmod.length) { 
		if(JB.Scripte.gra==0) {
			JB.Scripte.gra = 1;
			JB.LoadScript(JB.GPX2GM.Path+'gra_canvas.js', function(){ JB.Scripte.gra = 2; });
//			JB.LoadScript(JB.GPX2GM.Path+'gra_svg.js', function(){ JB.Scripte.gra = 2; });
			JB.Scripte.plot = 1;
			JB.LoadScript(JB.GPX2GM.Path+"plot.js", function(){ JB.Scripte.plot = 2; }); 
			JB.Debug_Info(ID,"Grafikscripte werden geladen",false);
		}
	}
	
	this.ShowGPX = function(fn,mpt) {
		var filenames = [];
		file = []; 
		for(var i=0;i<fn.length;i++) {
			if(typeof fn[i] === "string") file[i] = { name:this.parameters.gpxpfad+fn[i] , fileobject:null };
			else if(typeof fn[i] === "object") file[i] = { name:this.parameters.gpxpfad+fn[i].name , fileobject:fn[i] };
			filenames[i] = file[i].name;
		}
		maptype = mpt;
		JB.Debug_Info(id,"ShowGPX, Filename(s): "+filenames.join(","),false);

		var infodiv = document.createElement("div");
		JB.addClass("JBinfodiv",infodiv);
		infodiv.innerHTML = strings.wait.length > 0 ? strings.wait : "<br><img src='" + JB.GPX2GM.Path + "Icons/Loading_icon.gif'>";
		div.appendChild(infodiv);
		JB.Debug_Info(id,"Info da",false);
		JB.Debug_Info(id,"Lade "+filenames.join(","),false);
		JB.lpgpx.call(this,file,id,function(daten) {
			newfile = true;
			gpxdaten = daten;
			JB.Wait(id,["maputils"],(function() {JB.getTimezone.call(dieses,gpxdaten,{trackinfo:trackinfo,wpinfo:wpinfo})}));
			gpxdaten = pict2WP.call(this,gpxdaten);
			gpxdaten = div2WP.call(this,gpxdaten);
			if(this.parameters.tracksort) gpxdaten = sort_tracks.call(this,gpxdaten);
			gpxdaten = wp_dist.call(this,gpxdaten);
			setMapHead.call(this);
			if (this.parameters.legende) {
				JB.onresize(odiv, function(w,h) {
					var t = MapHead.offsetHeight;
					mapdiv.style.top = t +"px";
					mapdiv.style.height = (h-t) + "px";
				},true)
			}
			show.call(this);
			div.removeChild(infodiv);
			JB.Debug_Info(id,"Info weg",false);
		});
	} // ShowGPX

	this.Rescale = function() {
		var daten;
		if(arguments.length == 0) daten = gpxdaten;
		else if(arguments.length == 1) daten = arguments[0];
		else if(arguments.length == 3) daten = JB.bounds(arguments[0],arguments[1],arguments[2]); 
		//                             daten = JB.bounds(center_lat,  center_lon,  radius); 
		JB.Debug_Info(id,"Rescale: lat: "+daten.latmin+"..."+daten.latmax+", lon: "+daten.lonmin+"..."+daten.lonmax,false);
		Map.rescale(daten);
	} // Rescale

	this.GetMap = function() {
		return Map;
	} // GetMap

	this.Clear = function() {
	  var p,pr,i;
		if(zoomchangeevent1) Map.removeEvent(zoomchangeevent1);
		if(zoomchangeevent2) Map.removeEvent(zoomchangeevent2);
		Map = null;
		for(p in profil) {
			pr = profil[p];                                                                  
			if(pr.diag) pr.diag.clear();                       
		}
		profil = null;	
		gpxdaten = null;
		for(i=0;i<markers.length;i++) JB.RemoveElement(markers[i]);
		markers = [];
		for(i=0;i<trackpolylines.length;i++) JB.RemoveElement(trackpolylines[i]);
		trackpolylines = [];
		for(i=0;i<routepolylines.length;i++) JB.RemoveElement(routepolylines[i]);
		routepolylines = [];
		while(div.hasChildNodes()) div.removeChild(div.firstChild);
		JB.offresize(fb_onresize);
	} // Clear

	function wp_dist(daten) { 
		JB.Debug_Info(id,"wp_dist ...",false);
		var wp = daten.wegpunkte.wegpunkt;
		var wpi,wpj;
		for(var i=0;i<wp.length;i++) {
			wpi = wp[i]; 
			wp[i].dist = [];
			for(var j=0;j<wp.length;j++) {
				wpj = wp[j];
				JB.entf.init(wpi.lat,wpi.lon,0.0);
				wp[i].dist[j] = [j,JB.entf.rechne(wpj.lat,wpj.lon,0.0)];
			}
			wp[i].dist.sort(function(a,b){return a[1]-b[1]}); 
			wp[i].cluster = -1;
		}
		daten.wegpunkte.wegpunkt = wp;
		JB.Debug_Info(id,"fertig",false);
		return daten;
	} // wp_dist

	function sort_tracks(daten) {
		JB.Debug_Info(id,"sort_tracks ...",false);
		if(this.parameters.tracks_dateiuebergreifend_verbinden) {
			daten.tracks.track.sort(function(a,b){
				return(a.t0-b.t0);
			});
			var x0,t0;
			for(var k=1;k<daten.tracks.track.length;k++) {
				x0 = daten.tracks.track[k-1].daten[daten.tracks.track[k-1].daten.length-1].x;
				t0 = daten.tracks.track[k-1].daten[daten.tracks.track[k-1].daten.length-1].t;
				for(var i=0;i<daten.tracks.track[k].daten.length;i++) {
					daten.tracks.track[k].daten[i].x += x0;
					daten.tracks.track[k].daten[i].t += t0;
				}
			}
		}
		else if(this.parameters.tracks_verbinden) {
			daten.tracks.track.sort(function(a,b){
				if(a.fnr<b.fnr) return -1;
				else if(a.fnr>b.fnr) return 1;
				else return(a.t0-b.t0);
			});
			var x0,t0;
			for(var k=1;k<daten.tracks.track.length;k++) {
				if(daten.tracks.track[k-1].fnr == daten.tracks.track[k].fnr) {
					x0 = daten.tracks.track[k-1].daten[daten.tracks.track[k-1].daten.length-1].x;
					t0 = daten.tracks.track[k-1].daten[daten.tracks.track[k-1].daten.length-1].t;
					for(var i=0;i<daten.tracks.track[k].daten.length;i++) {
						daten.tracks.track[k].daten[i].x += x0;
						daten.tracks.track[k].daten[i].t += t0;
					}
				}
			}
		}
		JB.Debug_Info(id,"fertig",false);
		return daten;
	} // sort_tracks
	
	function pict2WP(daten) {
		var pict = document.getElementById(ID+"_img");
		if(pict) {
			var geodata = pict.getAttribute("data-geo");
			if(geodata) {
				var bounds = {},t,e=false;
				geodata = geodata.split(",");
				if(geodata.length==3) {
					for(var i=0;i<3;i++) {
						t = geodata[i].split(":");
						if(t.length == 2) bounds[t[0]] = parseFloat(t[1]);
						else e = true;
					}
				}
				if(!e) {
					JB.Debug_Info(id,"Im Bilderbereich Bounds gefunden.",false);
					var bounds = JB.bounds(bounds.centerlat,bounds.centerlon,bounds.radius);
					if(bounds.latmin<daten.latmin) daten.latmin=bounds.latmin; if(bounds.latmax>daten.latmax) daten.latmax=bounds.latmax;
					if(bounds.lonmin<daten.lonmin) daten.lonmin=bounds.lonmin; if(bounds.lonmax>daten.lonmax) daten.lonmax=bounds.lonmax;
				}
				else
					JB.Debug_Info(id,"Fehler bei Bounds in Pict-Div",false);
			}
			var im = pict.querySelectorAll("img, a");
			JB.Debug_Info(id,im.length +" Bilder zum Geotaggen gefunden",false);
			for(var i=0;i<im.length;i++) {
				var geodata = im[i].getAttribute("data-geo");
				if(geodata) {
					geodata = geodata.split(",");
					if(geodata.length==2) {
						var wp = {};
						for(var j=0;j<2;j++) {
							var par = geodata[j].split(":");
							if(par.length==2) {
								wp[par[0]] = parseFloat(par[1]);
							}
						}
						if(wp.lat && wp.lon) {
							if(!this.parameters.usegpxbounds) {
								if(wp.lat<daten.latmin) daten.latmin=wp.lat; if(wp.lat>daten.latmax) daten.latmax=wp.lat;
								if(wp.lon<daten.lonmin) daten.lonmin=wp.lon; if(wp.lon>daten.lonmax) daten.lonmax=wp.lon;
							}
							if(wp.lat<daten.wegpunkte.latmin) daten.wegpunkte.latmin=wp.lat; if(wp.lat>daten.wegpunkte.latmax) daten.wegpunkte.latmax=wp.lat;
							if(wp.lon<daten.wegpunkte.lonmin) daten.wegpunkte.lonmin=wp.lon; if(wp.lon>daten.wegpunkte.lonmax) daten.wegpunkte.lonmax=wp.lon;
							if(im[i].alt) wp.cmt = im[i].alt; 
							else if (im[i].innerHTML) wp.cmt = im[i].innerHTML;
							else wp.cmt = "";
							wp.desc = ""; //wp.cmt;
							wp.link = im[i].getAttribute("data-link")?im[i].getAttribute("data-link"):"" ;
							wp.sym = "default";
							wp.time = 0;
							if(im[i].src)	wp.name = im[i].src;
							else if(im[i].href) wp.name = im[i].href;
							else wp.name = "";
							daten.wegpunkte.wegpunkt.push(wp);
						}
					}
				}
			}
			daten.wegpunkte.anzahl = daten.wegpunkte.wegpunkt.length;
		}
		return daten;
	} // pict2WP

	function div2WP(daten) { 
		var divs = document.getElementById(ID+"_wp");
		if(divs) {
			var geodata = divs.getAttribute("data-geo");
			if(geodata) {
				var bounds = {},t,e=false;
				geodata = geodata.split(",");
				if(geodata.length==3) {
					for(var i=0;i<3;i++) {
						t = geodata[i].split(":");
						if(t.length == 2) bounds[t[0]] = parseFloat(t[1]);
						else e = true;
					}
				}
				if(!e) {
					JB.Debug_Info(id,"Im Wegpunktbereich Bounds gefunden.",false);
					var bounds = JB.bounds(bounds.centerlat,bounds.centerlon,bounds.radius);
					if(bounds.latmin<daten.latmin) daten.latmin=bounds.latmin; if(bounds.latmax>daten.latmax) daten.latmax=bounds.latmax;
					if(bounds.lonmin<daten.lonmin) daten.lonmin=bounds.lonmin; if(bounds.lonmax>daten.lonmax) daten.lonmax=bounds.lonmax;
				}
				else
					JB.Debug_Info(id,"Fehler bei Bounds in WP-Div",false);
			}
			var dv = divs.querySelectorAll("div");
			JB.Debug_Info(id,dv.length +" Divs zum Geotaggen gefunden",false);
			for(var i=0;i<dv.length;i++) {
				var geodata = dv[i].getAttribute("data-geo");
				if(geodata) {
					geodata = geodata.split(",");
					if(geodata.length==2) {
						var wp = {};
						for(var j=0;j<2;j++) {
							var par = geodata[j].split(":");
							if(par.length==2) {
								wp[par[0]] = parseFloat(par[1]);
							}
						}
						if(wp.lat && wp.lon) {
							if(!this.parameters.usegpxbounds) {
								if(wp.lat<daten.latmin) daten.latmin=wp.lat; if(wp.lat>daten.latmax) daten.latmax=wp.lat;
								if(wp.lon<daten.lonmin) daten.lonmin=wp.lon; if(wp.lon>daten.lonmax) daten.lonmax=wp.lon;
							}
							if(wp.lat<daten.wegpunkte.latmin) daten.wegpunkte.latmin=wp.lat; if(wp.lat>daten.wegpunkte.latmax) daten.wegpunkte.latmax=wp.lat;
							if(wp.lon<daten.wegpunkte.lonmin) daten.wegpunkte.lonmin=wp.lon; if(wp.lon>daten.wegpunkte.lonmax) daten.wegpunkte.lonmax=wp.lon;
							wp.cmt = dv[i].innerHTML?dv[i].innerHTML:"";
							wp.desc = ""; // wp.cmt;
							wp.link = dv[i].getAttribute("data-link")?dv[i].getAttribute("data-link"):"" ;
							wp.sym = dv[i].getAttribute("data-icon")?dv[i].getAttribute("data-icon"):"default" ;
							wp.time = 0;
							wp.name = dv[i].getAttribute("data-name")?dv[i].getAttribute("data-name"):"";
							daten.wegpunkte.wegpunkt.push(wp);
						}
					}
				}
			}
			daten.wegpunkte.anzahl = daten.wegpunkte.wegpunkt.length;
		}
		return daten;
	} // div2WP

	var chkwpt,chktrk,chkrt;
	function setMapHead() {
		JB.Debug_Info(id,"setMapHead",false);
		var dieses = this;
		var str = "<div>";
		if(div.title) {
			str += div.title + "&nbsp;";
		}
		else {
			if(this.parameters.legende_fnm) {
				var shdate = this.parameters.legende_fnm_lm === true || this.parameters.legende_fnm_lm ==="d" || this.parameters.legende_fnm_lm === "dt";
				var shtime = this.parameters.legende_fnm_lm ==="t" || this.parameters.legende_fnm_lm === "dt";
				str += "<span class='visually-hidden'>"+strings.file+": </span>"
				for(var i=0;i<file.length;i++) {
					str += file[i].name.replace(/.+\//,"");
					if((shdate || shtime) && file[i].filelastmod) {
						str += "(";
						if(shdate && file[i].filelastmod.d) str += file[i].filelastmod.d;
						if(shdate && shtime && file[i].filelastmod.d && file[i].filelastmod.t) str += ",";
						if(shtime && file[i].filelastmod.t) str += file[i].filelastmod.t;						
						str += ")";
					}
					if(i<file.length-1) str += ", ";
					else                str += ":&nbsp;";
				}
			}
		}
		str += "</div>";
		MapHead.innerHTML = str;
		MapHead.appendChild(JB.createImageButton({src:JB.GPX2GM.Path+"Icons/lupe_p.png",alt:"Zoom-Symbol"},"menueitem",JB.GPX2GM.strings[JB.GPX2GM.parameters.doclang].zoom,function() { dieses.Rescale() }));
		var gpxelement,name,texte;
		if(gpxdaten.wegpunkte.anzahl) {
			if(gpxdaten.wegpunkte.anzahl==1) {
				gpxelement = strings.wpt;
				name = gpxdaten.wegpunkte.wegpunkt[0].name;
				if(JB.checkImageName(name)) texte = [name.replace(/.+\//,"")];
				else texte = [name];
			}
			else if(gpxdaten.wegpunkte.anzahl>1) {
				gpxelement = strings.wpts;
				var texte = [strings.all];
				for(var i=0;i<gpxdaten.wegpunkte.anzahl;i++) {
					name = gpxdaten.wegpunkte.wegpunkt[i].name;
					if(JB.checkImageName(name)) texte[i+1] = name.replace(/.+\//,"");
					else texte[i+1] = name;
				}
			}
			var zoomFunc = []; 
			if(gpxdaten.wegpunkte.anzahl > 1) zoomFunc[0] = function() { dieses.Rescale(gpxdaten.wegpunkte) }; 
			for(var i=0;i<gpxdaten.wegpunkte.anzahl;i++) {
				var lat = gpxdaten.wegpunkte.wegpunkt[i].lat;
				var lon = gpxdaten.wegpunkte.wegpunkt[i].lon;
				var wpdaten = {};
				wpdaten.latmin = lat - 1e-3;
				wpdaten.latmax = lat + 1e-3;
				wpdaten.lonmin = lon - 1e-3;
				wpdaten.lonmax = lon + 1e-3;
				(function(daten) {
					zoomFunc.push( function() { dieses.Rescale(daten) } );
				})(wpdaten);
			};
			chkwpt = new JB.CheckBoxGroup(this,MapHead.id,texte,gpxelement,ID+"_wpt",[],this.parameters.legende_wpt,show.bind(dieses),zoomFunc);
		}
		if(gpxdaten.tracks.anzahl) {
			texte = [];
			if(gpxdaten.tracks.anzahl==1) {
				gpxelement = strings.trk;
				if(this.parameters.legende_rr) {
					texte[0] = gpxdaten.tracks.track[0].name+" ("+Number(gpxdaten.tracks.track[0].laenge.toPrecision(10).toString(10))+units.way;
					if(typeof(gpxdaten.tracks.track[0].rauf)!="undefined") 
						texte[0] += ", +"+gpxdaten.tracks.track[0].rauf+units.alt+", -"+gpxdaten.tracks.track[0].runter+units.alt+") ";
					else 
						texte[0] += ") ";
				}
				else
					texte[0] = gpxdaten.tracks.track[0].name+" ("+Number(gpxdaten.tracks.track[0].laenge.toPrecision(10).toString(10))+units.way+") ";
			}
			else if(gpxdaten.tracks.anzahl>1) { 
				gpxelement = strings.trks;
				if(this.parameters.legende_rr) {
					var rrflag=true;  
					for(var i=0;i<gpxdaten.tracks.anzahl;i++) {
						texte[i+1] = gpxdaten.tracks.track[i].name+" ("+Number(gpxdaten.tracks.track[i].laenge.toPrecision(10).toString(10))+units.way;
						if(typeof(gpxdaten.tracks.track[i].rauf)!="undefined") {
							texte[i+1] += ", +"+ gpxdaten.tracks.track[i].rauf +units.alt+", -"+gpxdaten.tracks.track[i].runter+units.alt+") ";
						}
						else {
							texte[i+1] += ")";
							rrflag = false;
						}
					}
					texte[0] = strings.all+" ("+Number(gpxdaten.tracks.laenge.toPrecision(10).toString(10))+units.way
					if(rrflag) texte[0] += ", +"+gpxdaten.tracks.rauf+units.alt+", -"+gpxdaten.tracks.runter+units.alt+") ";
					else       texte[0] += ") ";
				}
				else {
					texte[0] = strings.all+" ("+Number(gpxdaten.tracks.laenge.toPrecision(10).toString(10))+units.way+") ";
					for(var i=0;i<gpxdaten.tracks.anzahl;i++) texte[i+1] = gpxdaten.tracks.track[i].name+" ("+Number(gpxdaten.tracks.track[i].laenge.toPrecision(10).toString(10))+units.way+")";
				}
			}
			var farben = []; for(var i=0;i<gpxdaten.tracks.anzahl;i++) farben[i] = gpxdaten.tracks.track[i].farbe;
			var zoomFunc = [];
			zoomFunc[0] = function() { dieses.Rescale(gpxdaten.tracks) }
			for(var i=0;i<gpxdaten.tracks.anzahl;i++) {
				(function(daten) {
					zoomFunc[i+1] = function() { dieses.Rescale(daten) };
				})(gpxdaten.tracks.track[i]);
			};
			chktrk = new JB.CheckBoxGroup(this,MapHead.id,texte,gpxelement,ID+"_trk",farben,this.parameters.legende_trk,show.bind(dieses),zoomFunc);
		}
		if(gpxdaten.routen.anzahl) {
			var texte = [];
			if(gpxdaten.routen.anzahl==1) {
				gpxelement = strings.rte;
				texte[0] = gpxdaten.routen.route[0].name+" ("+Number(gpxdaten.routen.route[0].laenge.toPrecision(10).toString(10))+units.way+") ";
		}
			else if(gpxdaten.routen.anzahl>1) {
				gpxelement = strings.rtes;
				texte[0] = "Alle"+" ("+Number(gpxdaten.routen.laenge.toPrecision(10).toString(10))+units.way+") ";
				for(var i=0;i<gpxdaten.routen.anzahl;i++) texte[i+1] = gpxdaten.routen.route[i].name+" ("+Number(gpxdaten.routen.route[i].laenge.toPrecision(10).toString(10))+units.way+") ";
			}
			var farben = []; for(var i=0;i<gpxdaten.routen.anzahl;i++) farben[i] = gpxdaten.routen.route[i].farbe;
			var zoomFunc = [];
			zoomFunc[0] = function() { dieses.Rescale(gpxdaten.routen) }
			for(var i=0;i<gpxdaten.routen.anzahl;i++) {
				(function(daten) {
					zoomFunc[i+1] = function() { dieses.Rescale(daten) };
				})(gpxdaten.routen.route[i]);
			};
			chkrt = new JB.CheckBoxGroup(this,MapHead.id,texte,gpxelement,ID+"_rt",farben,this.parameters.legende_rte,show.bind(dieses),zoomFunc);
		}
		new JB.activateCheckBoxGroups(id,MapHead);
	} // setMapHead
	
	var profilcanvas="X";
	var zoomchangeevent1=null,zoomchangeevent2=null;

	function show() {
		var dieses = this;
		JB.Debug_Info(id,"show",false);
		if(dieses.parameters.profilflag) {
			JB.Wait(ID,["gra","plot"], function() { 
				showProfiles.call(dieses); 
				if(profilcanvas=="X") {
					profilcanvas = document.getElementById(ID+"_profiles");
					if(profilcanvas) 
						JB.onresize(profilcanvas,function(w,h) {
							for(var p in profil) {
								var pr = profil[p];                                                
								if(pr.ele) {
									pr.diag.clear();  
									pr.diag = null;
								}
							}
							showProfiles.call(dieses);
						});
				}
			}); 
		}
		JB.Wait(id,["maplib","maputils"],function() {
			if(!Map) {
				if(typeof(JB.GPX2GM.callback)=="function") 
					JB.GPX2GM.callback({id:id,type:"Map_v",gpxdaten:gpxdaten,profil:profil,Map:Map});
				Map = new JB.Map(dieses);
				JB.Debug_Info(ID,"Karte erstellt",false);
				if(typeof(JB.GPX2GM.callback)=="function") 
					JB.GPX2GM.callback({id:id,type:"Map_n",gpxdaten:gpxdaten,profil:profil,Map:Map});
			}
			if(newfile) { 
				if (maptype!="") Map.change(maptype); 
				if(mapdiv.offsetWidth*mapdiv.offsetHeight!=0) dieses.Rescale(); 
				else {
					var resev = JB.onresize(mapdiv,function(w,h){
						dieses.Rescale();
						JB.offresize(resev);
					});
				}
				newfile = false;
			}
			showTracks.call(dieses);
			showRoutes.call(dieses);
			var zoomchangedevent = (dieses.parameters.mapapi=="gm")?"zoom_changed":"zoomend" ;
			if(zoomchangeevent2) Map.removeEvent(zoomchangeevent2);
			if( dieses.parameters.arrowtrack || dieses.parameters.arrowroute ) 
				zoomchangeevent2 = Map.addMapEvent(zoomchangedevent, function(){
					showTracks.call(dieses);
					showRoutes.call(dieses);
				});
			if(dieses.parameters.wpcluster) { 
				if(dieses.parameters.mapapi=="gm") Map.addMapEventOnce("idle", function() { showWpts.call(dieses) });
				else showWpts.call(dieses);
				if(!zoomchangeevent1) zoomchangeevent1 = Map.addMapEvent(zoomchangedevent, function(){ 
					if(dieses.parameters.mapapi=="gm") Map.addMapEventOnce("idle", function() { showWpts.call(dieses) });
					else showWpts.call(dieses);
				});
				else showWpts.call(dieses);
			}
			else {
				if(zoomchangeevent1) Map.removeEvent(zoomchangeevent1);
				showWpts.call(dieses);
			}
		});
	} // show
	
	function showWpts() {
		var mrk;
		JB.Debug_Info(id,"showWpts",false);
		for(var i=0;i<markers.length;i++) JB.RemoveElement(markers[i]);
		markers = [];
		if (!(chkwpt && chkwpt.status[0])) return;
		if(gpxdaten.wegpunkte.anzahl>0 && typeof(JB.GPX2GM.callback)=="function") 
			JB.GPX2GM.callback({id:id,type:"Wegpunkte_v",gpxdaten:gpxdaten,profil:profil,Map:Map});
		if(this.parameters.wpcluster && gpxdaten.wegpunkte.anzahl>1) { 
			var clusters = wpcluster();
			mrk = showClusters.call(this,clusters);
			markers = markers.concat(mrk);
		}
		for(var i=0;i<gpxdaten.wegpunkte.anzahl;i++) {
			if(gpxdaten.wegpunkte.wegpunkt[i].cluster == -1 && chkwpt.status[gpxdaten.wegpunkte.anzahl==1?0:i+1]) {
				mrk = showWpt.call(this,gpxdaten.wegpunkte.wegpunkt[i]);
				markers = markers.concat(mrk);
			}
		}
		if(markers.length>0 && typeof(JB.GPX2GM.callback)=="function")
			JB.GPX2GM.callback({id:id,type:"Wegpunkte_n",gpxdaten:gpxdaten,profil:profil,Map:Map,markers:markers});
	} // showWpts 
	
	function showWpt(waypoint) {
		var sym = waypoint.sym.toLowerCase() ;
		var icon = JB.icons[sym]?JB.icons[sym]:null;
		JB.Debug_Info(id,"Symbol: "+sym,false);
		var imgsrc="";
		if (JB.checkImageName(waypoint.name)) imgsrc = waypoint.name;
		else if (JB.checkImageName(waypoint.link)) imgsrc = waypoint.link;
		wpinfo.call(this,waypoint);
		var mrk;
		if(imgsrc.length) {
			if(this.parameters.bildwegpunkticon != "") sym = this.parameters.bildwegpunkticon;
			mrk = Map.Marker_Bild(waypoint,JB.icons[sym]?JB.icons[sym]:JB.icons.Bild,this.parameters.bildpfad+imgsrc);
		}
		else if (waypoint.link && waypoint.link.length)
			mrk = Map.Marker_Link(waypoint,icon,waypoint.name,waypoint.link,this.parameters.popup_Pars);
		else if (waypoint.name.length || waypoint.cmt.length || waypoint.desc.length)
			mrk = Map.Marker_Text(waypoint,icon,waypoint.name);
		else
			mrk = Map.Marker(waypoint,icon);
		return mrk;
	} // showWpt
	
	function showClusters(clusters) {
		var zoomstatus = Map.getZoom();
		var mrks=[],mrk;
		for(var i=0;i<clusters.length;i++) {
			var cluster = clusters[i];
			if(zoomstatus.zoom<zoomstatus.maxzoom) {
				JB.Debug_Info(id,"Symbol: Cluster",false);
				mrk = Map.Marker_Cluster(cluster,gpxdaten.wegpunkte.wegpunkt,strings);
				mrks = mrks.concat(mrk);
			}
			else {
				var mindist = 40.0/Map.getPixelPerKM(gpxdaten);
				var dphi = 2*Math.PI/cluster.members.length;
				for(var j=0;j<cluster.members.length;j++) {
					var wporg = gpxdaten.wegpunkte.wegpunkt[cluster.members[j]];
					var wpcopy = {},e;
					for(e in wporg) wpcopy[e] = wporg[e];
					wpcopy.lat = cluster.lat + mindist*Math.cos(j*dphi)*180/(6378.137*Math.PI);
					wpcopy.lon = cluster.lon + mindist*Math.sin(j*dphi)*180/(6378.137*Math.PI*Math.cos(cluster.lat*Math.PI/180));
					mrk = showWpt.call(this,wpcopy);
					mrks = mrks.concat(mrk);
					mrks.push(Map.simpleLine(wporg.lat,wporg.lon,wpcopy.lat,wpcopy.lon));
				}
			}
		}
		return mrks;
	}

	function wpcluster() {
		var wps = gpxdaten.wegpunkte.wegpunkt;
		var mindist = 40.0/Map.getPixelPerKM(gpxdaten);
		var clusters = [];
		var wppointer = [];
		for(var i=0;i<wps.length;i++) {
			for(var ct=0;ct<wps.length;ct++) if(wps[i].dist[ct][1]>mindist) break;
			wppointer[i] = [i,ct];
		}
		wppointer.sort(function(a,b) {return a[1]-b[1];});
		var clusternr=-1;
		for(var i=0;i<wps.length;i++) wps[i].cluster = -1;
		for(var ii=0;ii<wps.length;ii++) { 
			var i= wppointer[ii][0];
			var wp = wps[i];
			if(wp.cluster==-1 && wp.dist[1][1]<mindist) {
				clusternr = clusters.length;
				var cluster = {lat:0, lon:0, members: []};
				for(var j=0;j<wp.dist.length;j++) { 
					if(wp.dist[j][1]<mindist) { 
						if(wps[wp.dist[j][0]].cluster==-1) {
							cluster.members.push(wp.dist[j][0]);
							wps[wp.dist[j][0]].cluster = clusternr; 
						} 
					}
				}	
				if(cluster.members.length>1) clusters.push(cluster);
				else if(cluster.members.length==1) wps[cluster.members[0]].cluster = -1;
			}
		}
		for(var i=0;i<wps.length;i++) {
			var wp = wps[i];
			if(wp.cluster==-1) {
				for(var j=0;j<wp.dist.length;j++) { 
					if(wp.dist[j][1]<mindist) { 
						if(wps[wp.dist[j][0]].cluster>-1) {
							wps[i].cluster = wps[wp.dist[j][0]].cluster;
							clusters[wps[i].cluster].members.push(i);
							break;
						}
					}
				}
			}
		}
		for(var i=0;i<clusters.length;i++) {
			var lat=0,lon=0;
			for(var j=0;j<clusters[i].members.length;j++) {
				var wp = wps[clusters[i].members[j]];
				lat += wp.lat;
				lon += wp.lon;
			}
			clusters[i].lat = lat/clusters[i].members.length;
			clusters[i].lon = lon/clusters[i].members.length;
		}
		JB.Debug_Info(id,clusters.length+" Wegpunktcluster angelegt",false);
		return clusters;
	} // wpcluster

	function showRoutes() {
		JB.Debug_Info(id,"showRoutes",false);
		for(var i=0;i<routepolylines.length;i++) JB.RemoveElement(routepolylines[i]);
		routepolylines = [];
		if (!(chkrt && chkrt.status[0])) return;
		if(gpxdaten.routen.anzahl>0 && typeof(JB.GPX2GM.callback)=="function")
			JB.GPX2GM.callback({id:id,type:"Routen_v",gpxdaten:gpxdaten,profil:profil,Map:Map});
		for(var i=0;i<gpxdaten.routen.anzahl;i++) if(chkrt.status[gpxdaten.routen.anzahl==1?0:i+1]) {
			var routei = gpxdaten.routen.route[i];
			var info = "";
			routinfo.call(this,routei);
			var controls = {
				col: routei.farbe,
				ocol: this.parameters.ocol,
				opac: this.parameters.ropac,
				width: this.parameters.rwidth,
				owidth: this.parameters.owidth
			}
			var rts = Map.Polyline(routei,controls,"Route");
			routepolylines = routepolylines.concat(rts);
			if(this.parameters.shrtstart) {
			  rts = Map.Marker(routei.daten[0],JB.icons.start);
				routepolylines = routepolylines.concat(rts);
			}
			if(this.parameters.shrtziel) {
				rts = Map.Marker(routei.daten[routei.daten.length-1],JB.icons.finish)
				routepolylines = routepolylines.concat(rts);
			}
			var delta = this.parameters.routemarker;
			if(delta.length && !isNaN(delta)) {
				rts = streckenmarker(delta,routei.daten);
				routepolylines = routepolylines.concat(rts);
			}
		}
		if(routepolylines.length>0 && typeof(JB.GPX2GM.callback)=="function") 
			JB.GPX2GM.callback({id:id,type:"Routen_n",gpxdaten:gpxdaten,profil:profil,Map:Map,polylines:routepolylines});
	} // showRoutes

	function showTracks() {
		var colmod=this.parameters.trcolmod,colmodflag=false,min=1e10,max=-1e10,minmax={};
		JB.Debug_Info(id,"showTracks",false);
		for(var i=0;i<trackpolylines.length;i++) JB.RemoveElement(trackpolylines[i]);
		trackpolylines = [];
//		if(colmod.length) {
			if(FB) FB.del();
			JB.offresize(fb_onresize);
//		}
		if (!(chktrk && chktrk.status[0])) return;
		if(gpxdaten.tracks.anzahl>0 && typeof(JB.GPX2GM.callback)=="function") 
			JB.GPX2GM.callback({id:id,type:"Tracks_v",gpxdaten:gpxdaten,profil:profil,Map:Map});
		if( (colmod=="h" && gpxdaten.tracks.hflag) || (colmod=="v" && gpxdaten.tracks.tflag) || (colmod=="hr" && gpxdaten.tracks.hrflag)
		      || (colmod=="cad" && gpxdaten.tracks.cadflag) || (colmod=="atemp" && gpxdaten.tracks.atempflag) ) {
			colmodflag = true;
			var coltab = JB.farbtafel(1000);
			for(var i=0;i<gpxdaten.tracks.anzahl;i++) if(chktrk.status[gpxdaten.tracks.anzahl==1?0:i+1]) {
				var tracki = gpxdaten.tracks.track[i];
				if(colmod=="h" && tracki.hflag) {
					if(typeof(JB.Scaling)!="undefined" && typeof(JB.Scaling.hmin)!="undefined" && typeof(JB.Scaling.hmax)!="undefined") {
						minmax.min = JB.Scaling.hmin;
						minmax.max = JB.Scaling.hmax;
						if(!JB.Scaling.hardscaling) minmax = getminmax(tracki.daten,"h",minmax);
					}
					else 
						minmax = getminmax(tracki.daten,"h");
				}
				else if(colmod=="v" && tracki.tflag) {
					if(typeof(JB.Scaling)!="undefined" && typeof(JB.Scaling.vmin)!="undefined" && typeof(JB.Scaling.vmax)!="undefined") {
						minmax.min = JB.Scaling.vmin;
						minmax.max = JB.Scaling.vmax;
						if(!JB.Scaling.hardscaling) minmax = getminmax(tracki.daten,"v",minmax);
					}
					else 
						minmax = getminmax(tracki.daten,"v");
				}
				else if(colmod=="hr" && tracki.hrflag) {
					if(typeof(JB.Scaling)!="undefined" && typeof(JB.Scaling.hrmin)!="undefined" && typeof(JB.Scaling.hrmax)!="undefined") {
						minmax.min = JB.Scaling.hrmin;
						minmax.max = JB.Scaling.hrmax;
						if(!JB.Scaling.hardscaling) minmax = getminmax(tracki.daten,"hr",minmax);
					}
					else 
						minmax = getminmax(tracki.daten,"hr");
				}
				else if(colmod=="cad" && tracki.cadflag) {
					if(typeof(JB.Scaling)!="undefined" && typeof(JB.Scaling.cadmin)!="undefined" && typeof(JB.Scaling.cadmax)!="undefined") {
						minmax.min = JB.Scaling.cadmin;
						minmax.max = JB.Scaling.cadmax;
						if(!JB.Scaling.hardscaling) minmax = getminmax(tracki.daten,"cad",minmax);
					}
					else 
						minmax = getminmax(tracki.daten,"cad");
				}
				else if(colmod=="atemp" && tracki.atempflag) {
					if(typeof(JB.Scaling)!="undefined" && typeof(JB.Scaling.cadmin)!="undefined" && typeof(JB.Scaling.cadmax)!="undefined") {
						minmax.min = JB.Scaling.atempmin;
						minmax.max = JB.Scaling.atempmax;
						if(!JB.Scaling.hardscaling) minmax = getminmax(tracki.daten,"atemp",minmax);
					}
					else 
						minmax = getminmax(tracki.daten,"atemp");
				}
				min = Math.min(min,minmax.min); max = Math.max(max,minmax.max);
			}
		}
		else if(colmod=="s" && gpxdaten.tracks.hflag) {
			colmodflag = true;
			var coltab = JB.farbtafel_bipolar();
			for(var i=0;i<gpxdaten.tracks.anzahl;i++) if(chktrk.status[gpxdaten.tracks.anzahl==1?0:i+1]) {
				var tracki = gpxdaten.tracks.track[i];
				if(tracki.hflag) {
					if(typeof(JB.Scaling)!="undefined" && typeof(JB.Scaling.smin)!="undefined" && typeof(JB.Scaling.smax)!="undefined") {
						minmax.min = JB.Scaling.smin;
						minmax.max = JB.Scaling.smax;
						if(!JB.Scaling.hardscaling) minmax = getminmax(tracki.daten,"s",minmax);
					}
					else 
						minmax = getminmax(tracki.daten,"s");
				}
				min = Math.min(min,minmax.min); max = Math.max(max,minmax.max);
			}
			if(min*max<0) {
				if(-min<max) min = -max;
				else max = -min;
			}
			else {
				if(min>0) min = -max;
				else max = -min;
			}
		}
		if(colmodflag) {
			if(max<min) { max = 0.5; min = -0.5; }
			else if(max==min) { max += 0.5; min -= 0.5; }
			JB.Wait(ID,["gra","plot"], function() { 
			  if(!FB) FB = new JB.farbbalken(odiv);
				FB.create(0,30,10,coltab,min,max,profil[colmod+"p"].ytext);
				JB.Debug_Info(id,"Farbbalken für "+colmod+" erstellt.",false);
				fb_onresize = JB.onresize(odiv,function(w,h) {
					//if(!profil) return;
					FB.del();
					FB.create(0,30,10,coltab,min,max,profil[colmod+"p"].ytext);
				});
			});
		}
		for(var i=0;i<gpxdaten.tracks.anzahl;i++) if(chktrk.status[gpxdaten.tracks.anzahl==1?0:i+1]) {
			var tracki = gpxdaten.tracks.track[i];
			trackinfo.call(this,tracki);
			var controls = {
				col: tracki.farbe,
				ocol: this.parameters.ocol,
				opac: this.parameters.topac,
				width: this.parameters.twidth,
				owidth: this.parameters.owidth
			};
			var trs;
			if(colmodflag) {
				var cols=[],colindex;
				for(var j=0;j<tracki.daten.length;j++) {
					colindex = Math.round( (coltab.length-1) * (tracki.daten[j][colmod] - min)/(max - min) );
					colindex = Math.max(Math.min(colindex,coltab.length-1),0);
					cols[j] = coltab[colindex];
				}
				controls.width *= 2;
				trs = Map.Polyline(tracki,controls,"Track",cols,track_click_fkt);
			}
			else trs = Map.Polyline(tracki,controls,"Track",null,track_click_fkt);	
			trackpolylines = trackpolylines.concat(trs);
			if(this.parameters.shtrstart) {
				trs = Map.Marker(tracki.daten[0],JB.icons.start);
				trackpolylines = trackpolylines.concat(trs);
			}
			if(this.parameters.shtrziel) {
				trs = Map.Marker(tracki.daten[tracki.daten.length-1],JB.icons.finish)
				trackpolylines = trackpolylines.concat(trs);
			}
			var delta = this.parameters.trackmarker;
			if(delta.length && !isNaN(delta)) {
				trs = streckenmarker(delta,tracki.daten);
				trackpolylines = trackpolylines.concat(trs);
			}
		}
		if(trackpolylines.length>0 && typeof(JB.GPX2GM.callback)=="function") 
			JB.GPX2GM.callback({id:id,type:"Tracks_n",gpxdaten:gpxdaten,profil:profil,Map:Map,polylines:trackpolylines});
	} // showTracks
	
	function streckenmarker(delta,daten) {
		delta = Number(delta);
		var markerpos = delta, di, dim, marker, markers = [], dx, lat, lon;
		if(delta > daten[daten.length-1].x) return markers;
		if(delta < daten[daten.length-1].x/100) return markers;
		di = daten[0];
		for(var i=1;i<daten.length;i++) {
			dim = di;
			di = daten[i];
			if(di.x > markerpos) {
				dx = (markerpos - dim.x) / (di.x - dim.x);
				lat = dim.lat + dx * (di.lat - dim.lat);
				lon = dim.lon + dx * (di.lon - dim.lon);
				marker = Map.setDistanceMarker({lat:lat,lon:lon},JB.icons.Streckenmarker,markerpos,markerpos);
				markers = markers.concat(marker);
				markerpos += delta;
				dim = di;
			}
		}
		return markers;
	} // streckenmarker
	
	function track_click_fkt(map,daten,point) {
		var d, entf=[];
		for(var i=0;i<daten.length;i++) {
			JB.entf.init(point.lat,point.lng,0.0);
			d = JB.entf.rechne(daten[i].lat,daten[i].lon,0.0);
			entf.push({i:i,d:d});
		}
		entf.sort(function(a,b) {
			return a.d - b.d;
		});
		var a = daten[entf[0].i];
		var info = trackpointinfo(a);
		map.infowindow(info, a);
	} // track_click_fkt
	
	function trackinfo(tracki) {
		var info = "<strong>"+tracki.name+"</strong>";
		if(this.parameters.shtrx) 
			info += "<br />"+strings.way+":&nbsp;"+Number(tracki.laenge.toPrecision(10).toString(10))+"&nbsp;"+units.way;
		if(this.parameters.shtrtges && tracki.tges>0)
			info += "<br />"+strings.duration+": "+JB.Zeitstring(tracki.tges*3600);
		if(this.parameters.shtrtgeswob && tracki.tgeswob>0)
			info += "<br />"+strings.duration+"&nbsp;"+strings.inmo+": "+JB.Zeitstring(tracki.tgeswob*3600);
		if(this.parameters.shtrs && typeof(tracki.rauf)!="undefined" ) 
			info += "<br /><span style='white-space:nowrap;'>"+strings.altdiff+": +"+tracki.rauf+" "+units.alt+" / -"+tracki.runter+" "+units.alt+"</span>";
		if(this.parameters.shtrt && tracki.t0>0) 
			info += "<br />"+strings.tstart+":  <span style='white-space:nowrap;'>" + JB.sec2string.call(this,tracki.t0*3600,this.parameters.tdiff*3600+tracki.tzoff) + "</span>"; 
		if(this.parameters.shtrvmitt && tracki.vmitt>0)
			info += "<br /><span style='white-space:nowrap;'>"+strings.avspeed+" = " + tracki.vmitt + " "+units.speed+"</span>";
		if(this.parameters.shtrvmittwob && tracki.vmittwob>0)
			info += "<br /><span style='white-space:nowrap;'>"+strings.avspeed+" = " + tracki.vmittwob + " "+units.speed+" "+strings.inmo+"</span>";
		if(this.parameters.shtrvmittpace && tracki.vmitt>0)
			info += "<br /><span style='white-space:nowrap;'>"+strings.pace+" = " + (60/tracki.vmitt).toFixed(1) + " "+units.pace+"</span>";
		if(this.parameters.shtrvmittpacewob && tracki.vmittwob>0)
			info += "<br /><span style='white-space:nowrap;'>"+strings.pace+" = " + (60/tracki.vmittwob).toFixed(1) + " "+units.pace+" "+strings.inmo+"</span>";
		if(this.parameters.shtrcmt) info += "<br />"+tracki.cmt;
		if(this.parameters.shtrdesc) info += "<br />"+tracki.desc;
		tracki.info = info;
	} // trackinfo
	
	function routinfo(routei) {
			var info = "<strong>"+routei.name+"</strong>";
			if(this.parameters.shtrx)
				info += "<br />"+strings.way+"&nbsp;"+Number(routei.laenge.toPrecision(10).toString(10))+"&nbsp;"+units.way;
			if(this.parameters.shrtcmt) info += "<br />"+routei.cmt;
			if(this.parameters.shrtdesc) info += "<br />"+routei.desc;
			routei.info = info;
	} // routinfo
	
	function wpinfo(wp) {
		var imgsrc="";
		if (JB.checkImageName(wp.name)) imgsrc = wp.name;
		else if (JB.checkImageName(wp.link)) imgsrc = wp.link;
		var info = ((this.parameters.shwpname&&!imgsrc.length)?"<strong>"+wp.name+"</strong><br />":"")
						 + (this.parameters.shwpcmt?wp.cmt:"") 
						 + (this.parameters.shwpcmt&&this.parameters.shwpdesc?"<br />":"") 
						 + (this.parameters.shwpdesc?wp.desc:"");
		if(this.parameters.shwptime && wp.time>0) info += "<br /><span style='white-space:nowrap;'>("
																					+ JB.sec2string.call(this,wp.time,this.parameters.tdiff) +")</span>"; 
		wp.info = info;
	} // wpinfo
		
	function getminmax(daten,o,minmax) {
		var min=1e10,max=-1e10;
		if(typeof(minmax)!="undefined") { min = minmax.min; max = minmax.max; }
		for(var j=0;j<daten.length;j++) { 
			var wert = daten[j][o];
			if(wert<min) min = wert;
			if(wert>max) max = wert;
		}
		return {min:min,max:max};
	} // getminmax

	function showProfiles() {
		JB.Debug_Info(id,"showProfiles",false); 
		if(profil) profil.setflags(gpxdaten.tracks,-1);
		if(typeof(JB.GPX2GM.callback)=="function") 
			JB.GPX2GM.callback({id:id,type:"Profile_v",gpxdaten:gpxdaten,profil:profil,Map:Map});
		for(var p in profil) {
			if(profil[p].ele && !profil[p].diag) {
				profil[p].diag = new JB.plot(profil[p].id,profil[p].x,profil[p].y);
				if (profil[p].ele.className && profil[p].ele.className.search(/(^|\s)no_x(\s|$)/i)!=-1) profil[p].xtext = "";
				JB.Debug_Info(id,"Profil: "+profil[p].id+" Diagramm angelegt",false);
				profil[p].diag.framecol = this.parameters.plotframecol;
				profil[p].diag.gridcol = this.parameters.plotgridcol;
				profil[p].diag.labelcol = this.parameters.plotlabelcol;
				profil[p].diag.markercol = this.parameters.plotmarkercol;
				profil[p].diag.fillopac = this.parameters.profilfillopac;
				if(p.search("pt")>-1) {
					if(this.parameters.shtrtabs_p) profil[p].diag.xscaletime = "absolute";
					else                 profil[p].diag.xscaletime = "relative";
				}
			}
		}
		for(var p in profil) {
			var pr = profil[p];                                                
			if(pr.ele /*&& pr.pflag*/) pr.diag.clear();                       
		}
		if(!(chktrk && chktrk.status[0])) return;
		if(!gpxdaten) return;
		for(var i=0;i<gpxdaten.tracks.anzahl;i++) {
			var tracki = gpxdaten.tracks.track[i];
			var daten = tracki.daten;
			profil.setflags(tracki,-1);
			if(daten.length>1 && chktrk.status[gpxdaten.tracks.anzahl==1?0:i+1]) {
				for(var p in profil) { 
					pr = profil[p];
					if(pr.ele) {
						if(pr.scale && pr.scale.length==2) { 
							pr.scale[0][pr.x] = daten[0][pr.x];
							pr.scale[1][pr.x] = daten[daten.length-1][pr.x];
							pr.diag.scale(pr.scale);
							if(!JB.Scaling.hardscaling) pr.diag.scale(daten); 
						}
						else {
							pr.diag.scale(daten);
						}							
					}
				}
			}
		} 
		profil.setflags(gpxdaten.tracks,-1);
		for(var p in profil) { 
			var pr = profil[p]; 
			if(pr.ele) {
				pr.diag.frame(50,35,pr.xtext,pr.ytext); 
			}
		}
		for(var i=0;i<gpxdaten.tracks.anzahl;i++) if(chktrk.status[gpxdaten.tracks.anzahl==1?0:i+1]) { 
			var tracki = gpxdaten.tracks.track[i];
			if(tracki.daten.length>1) { 
				profil.setflags(tracki,-1);
				for(var p in profil) {
					var pr = profil[p];
					if(pr.ele && pr.pflag) {
						pr.diag.plot(tracki.daten,tracki.farbe);
					}						
				}
			}
		}
		var ct=0,cf=0;
		if(chktrk.status.length==1) {
			if(chktrk.status[0]) cf = ct = 1;
		}
		else {
			var fa={};
			for(var i=1;i<chktrk.status.length;i++) { 
				if(chktrk.status[i]) {
					ct++;
					var fnri = gpxdaten.tracks.track[i-1].fnr;
					if(!fa[fnri]) { fa[fnri] = 1; cf++; }
				}
			}
		}        
		if((cf==1 || this.parameters.tracks_dateiuebergreifend_verbinden) && (this.parameters.tracks_verbinden || ct==1)) {
			var d_t = [];
			profil.setflags(gpxdaten.tracks,ct);
			if(gpxdaten.tracks.anzahl==1) 
				d_t = d_t.concat(gpxdaten.tracks.track[0].daten);
			else
				for(var i=0;i<gpxdaten.tracks.anzahl;i++) if(chktrk.status[i+1]) d_t = d_t.concat(gpxdaten.tracks.track[i].daten);
			if(d_t.length) {
				for(var p in profil) {
					var pr = profil[p];
					if(pr.ele && pr.pflag) pr.diag.markeron(d_t,markerstart,markerstop,markermove,markerclick,"Linie") ;
				}
			}
		}
		if(typeof(JB.GPX2GM.callback)=="function") 
			JB.GPX2GM.callback({id:id,type:"Profile_n",gpxdaten:gpxdaten,profil:profil,Map:Map});
	} // showProfiles

	function markerstart() {
		JB.Debug_Info(id,"markerstart",false);
		JB.MoveMarker.init(Map,JB.icons.MoveMarker);
		profil.setflags(gpxdaten.tracks,-1);
		for(var p in profil) {
			var pr = profil[p];
			if(pr.ele && pr.pflag) pr.diag.showmarker("Linie");
		}
	} // markerstart
	function markerstop() {
		JB.Debug_Info(id,"markerstop",false);
		JB.MoveMarker.remove();
		profil.setflags(gpxdaten.tracks,-1);
		for(var p in profil) {
			var pr = profil[p];
			if(pr.ele && pr.pflag) pr.diag.hidemarker();
		}
	} // markerstop
	function markermove(p,a) {
		var info = trackpointinfo(a);
		profil.setflags(gpxdaten.tracks,-1);
		for(var pp in profil) {
			var pr = profil[pp];
			if(pr.ele && pr.pflag) pr.diag.setmarker(a,"Linie");
		}
		JB.MoveMarker.pos(a,info,dieses.parameters.maxzoomemove);
	} // markermove
	function markerclick(p,a) {
		var info = trackpointinfo(a);
		Map.gminfowindow(info,a);
	} // markerclick
	function trackpointinfo(a) {
		var info = "";
		if(dieses.parameters.shtrx)                                    info += strings.way+":&nbsp;"+a.x.toFixed(1)+units.way;
		if(dieses.parameters.shtrh &&    typeof a.h    != "undefined" && a.h != "nf") info += "<br />"+strings.alt+":&nbsp;"+Math.round(a.h)+units.alt;
		if(dieses.parameters.shtrrr &&   typeof a.rauf != "undefined") info += "&nbsp;(+"+Math.round(a.rauf)+units.alt+"/-"+Math.round(a.runter)+units.alt+")";
		if(dieses.parameters.shtrv &&    typeof a.v    != "undefined") info += "<br />"+strings.speed2+":&nbsp;"+Math.round(a.v)+units.speed;
		if(dieses.parameters.shtrpace && typeof a.v    != "undefined") info += "<br />"+strings.pace+":&nbsp;"+(60/a.v).toFixed(1)+units.pace;
		if(dieses.parameters.shtrs &&    typeof a.s    != "undefined") info += "<br />"+strings.grade+":&nbsp;"+Math.round(a.s)+strings.grade_unit;
		if(dieses.parameters.shtrhr &&   typeof a.hr   != "undefined") info += "<br />"+strings.hr+":&nbsp;"+Math.round(a.hr)+"&nbsp;"+strings.hr_unit;
		if(dieses.parameters.shtrcad &&  typeof a.cad  != "undefined") info += "<br />"+strings.cad+":&nbsp;"+Math.round(a.cad)+"&nbsp;"+strings.cad_unit;
		if(dieses.parameters.shtratemp && typeof a.atemp != "undefined") info += "<br />"+strings.temp+":&nbsp;"+Math.round(a.atemp)+"&nbsp;"+units.temp;
		if(dieses.parameters.shtrtabs_k && typeof a.t  != "undefined") info += "<br />"+strings.time+":&nbsp;"+JB.sec2string.call(dieses,a.tabs*3600,dieses.parameters.tdiff*3600);
		if(dieses.parameters.shtrt &&    typeof a.t    != "undefined") info += "<br />"+strings.time+":&nbsp;"+JB.Zeitstring(a.t*3600);
		if(dieses.parameters.shtrtwob && typeof a.twob != "undefined") info += "<br />"+strings.time+":&nbsp;"+strings.inmo+":&nbsp;"+JB.Zeitstring(a.twob*3600); 
		return info;
	} // trackpointinfo

} // JB.makeMap

JB.checkImageName = function(url) {
	var ext = url.substr(url.lastIndexOf(".")+1).toLowerCase();
	return (ext=="jpg" || ext=="jpeg" || ext=="png" || ext=="gif" || url.indexOf("data:image")>-1) ;
} //  checkImageName                 

JB.CheckBoxGroup = function(mapoject,id,Texte,gpxelement,Label,Farbe,def_stat,clickFunc,clickFunc2) {
	var dieses = this;
	var nbx = Texte.length;
	this.status = []; for(var i=0;i<nbx;i++) this.status[i] = def_stat ;
	var inp,label,img,span,button;
	var box = document.createElement("div");
	JB.addClass("JBcheckbox",box);
	var button = document.createElement("button");
	button.type = "button";
	button.setAttribute("aria-pressed",false);
	if(gpxelement.search("W")==0) button.id = id + "_Wegpunkte";
	else if(gpxelement.search("T")==0) button.id = id + "_Tracks";
	else if(gpxelement.search("R")==0) button.id = id + "_Routen";
	button.setAttribute("role","menuitem");
	button.innerHTML = "&nbsp;" + gpxelement + "<span class='visually-hidden'> " + JB.GPX2GM.strings[JB.GPX2GM.parameters.doclang].showlist + "</span>";
	box.appendChild(button);
	var ol = document.createElement("ol"),li;
	ol.setAttribute("aria-expanded",false);
	ol.setAttribute("role","menu");
	ol.style.maxHeight = "calc("+mapoject.mapdiv.offsetHeight+"px - 3em)";
	ol.style.overflowY = "auto";
	for(var i=0;i<nbx;i++) {
		var li = document.createElement("li");
		// li.setAttribute("role","menuitem");
		inp = document.createElement("input");
		inp.type = "checkbox";
		inp.id = Label + i;
		inp.nr = i;
		inp.setAttribute("role","menuitem");		
		if(i==0) inp.onclick = function() {
			var l = nbx;
			var n = Label;
			var status = this.checked;
			dieses.status[0] = status;
			for(var j=1;j<l;j++) {
				document.getElementById(n+j).checked = status;
				dieses.status[j] = status;
			}
			clickFunc();
		};
		else     inp.onclick = function() {
			var l = nbx;
			var n = Label;
			var status = false;
			for(var j=1;j<l;j++) status |= document.getElementById(n+j).checked;
//			document.getElementById(n+"0").checked = status;
			var ctchk = 0;
			for(var j=1;j<l;j++) if(document.getElementById(n+j).checked) ctchk++; 
			if(ctchk == 0) {
				document.getElementById(n+"0").checked = false;
				document.getElementById(n+"0").indeterminate = false;
			}
			else if(ctchk == l-1) {
				document.getElementById(n+"0").checked = true;
				document.getElementById(n+"0").indeterminate = false;
			}
			else {
				document.getElementById(n+"0").checked = false;
				document.getElementById(n+"0").indeterminate = true;
			}
			dieses.status[0] = status;
			dieses.status[this.nr] = this.checked;
			clickFunc();
		};
		li.appendChild(inp);
		label=document.createElement("label");
		label.setAttribute("for", inp.id);
		if(Farbe.length) {
			if(i==0 && nbx==1) label.style.color=Farbe[0];
			else if(i) label.style.color=Farbe[(i-1)%Farbe.length];
		}
		label.appendChild(document.createTextNode(Texte[i]));
		span = document.createElement("span");
		span.className = "visually-hidden";
		span.innerHTML = " "+JB.GPX2GM.strings[JB.GPX2GM.parameters.doclang].showhide+", ";
		label.appendChild(span);
		li.appendChild(label);
		inp.checked = def_stat;
		if(clickFunc2 && clickFunc2.length>i) {
			li.appendChild(JB.createImageButton({src:JB.GPX2GM.Path+"Icons/lupe_p.png",alt:"Zoom-Symbol"},"menueitem",JB.GPX2GM.strings[JB.GPX2GM.parameters.doclang].zoom,clickFunc2[i]));
		}
		ol.appendChild(li);
	}
	box.appendChild(ol);
	document.getElementById(id).appendChild(box);
} // JB.CheckBoxGroup

JB.createImageButton = function(image,role,infotext,clickFunc) {
	var button = document.createElement("button");
	button.type = "button";
	button.setAttribute("role",role);		
	var img = document.createElement("img");
	img.src = image.src;
	img.style.cursor = "Pointer";
	img.alt = image.alt;
	//img.title = infotext;
	button.appendChild(img);
	if(infotext && infotext.length) {
		var span = document.createElement("span");
		span.className = "visually-hidden";
		span.innerHTML = " "+infotext+".";
		button.appendChild(span);
	}
	button.onclick = clickFunc; 
	return button;
} // createImageButton

JB.activateCheckBoxGroups = function(MapId,MapHead) {
	
	this.MapId = MapId;
	
	var dieses = this;
	
	var handleMenuButtonClick = function(e) {
		e.preventDefault();
		for(var m in dieses.menuElements) {
			if(m != this.id) {
				dieses.menuElements[m].menuButton.setAttribute("aria-pressed", false);
				dieses.menuElements[m].ol.setAttribute("aria-expanded", false);
			}
		}
		var pressed = !(this.getAttribute("aria-pressed") === "true");
		this.setAttribute("aria-pressed", pressed);
		if(!pressed && dieses.active) dieses.menuElements[dieses.active].ol.setAttribute("aria-expanded", false);
		dieses.active = pressed?this.id:null;
		if(pressed && dieses.active) dieses.menuElements[dieses.active].ol.setAttribute("aria-expanded", true);
	}

	var handleMenuButtonFocus = function(e) { 
		e.preventDefault();
		for(var m in dieses.menuElements) {
			if(m != this.id) {
				dieses.menuElements[m].menuButton.setAttribute("aria-pressed", false);
				dieses.menuElements[m].ol.setAttribute("aria-expanded", false);
			}
		}
		this.setAttribute("aria-pressed", true);
		dieses.active = this.id;
		dieses.menuElements[dieses.active].ol.setAttribute("aria-expanded", true);
	}

	var keydown = function(event) {
		if(JB.GPX2GM.globalMapParameter.activeMapId!=dieses.MapId) return;
		var keyCode = event.keyCode;
		if(event.altKey) {
			switch(keyCode) {
				case 87: // w
					if(document.querySelector("#"+MapHead.id+"_Wegpunkte"))
						document.querySelector("#"+MapHead.id+"_Wegpunkte").click();	
				break;
				case 84: // t
					if(document.querySelector("#"+MapHead.id+"_Tracks"))
						document.querySelector("#"+MapHead.id+"_Tracks").click();	
				break;
				case 82: // r
					if(document.querySelector("#"+MapHead.id+"_Routen"))
						document.querySelector("#"+MapHead.id+"_Routen").click();	
				break;
			}
			return;
		}
		if(!dieses.active) return;	
		if(keyCode == 9) { // Tabulator
			dieses.menuElements[dieses.active].menuButton.setAttribute("aria-pressed", false);
			dieses.menuElements[dieses.active].activeLine = -1;
			dieses.menuElements[dieses.active].activeRow = 0;
			dieses.menuElements[dieses.active].ol.setAttribute("aria-expanded", false);
			dieses.active = null;
			return;
		}
		if(keyCode && (keyCode==27 || keyCode==37 || keyCode==38 || keyCode==39 || keyCode==40)) { 
			event.preventDefault();
			var menuElement = dieses.menuElements[dieses.active];
			switch(keyCode) {
				case 37: // links
					menuElement.activeRow = 1 - menuElement.activeRow;
					break;
				case 38: // rauf
					menuElement.activeLine --;
					if(menuElement.activeLine < 0) {
						menuElement.menuButton.setAttribute("aria-pressed", false);
						menuElement.activeLine = -1;
						menuElement.activeRow = 0;
						dieses.active = null;
						menuElement.ol.setAttribute("aria-expanded", false);
						return;
					}
					break;
				case 39: // rechts
					menuElement.activeRow = 1 - menuElement.activeRow;
					break;
				case 40: // runter
					menuElement.activeLine ++; 
					if(menuElement.activeLine >= menuElement.elements.length) menuElement.activeLine = menuElement.elements.length - 1;
					if(menuElement.activeLine == 0) {
						menuElement.menuButton.setAttribute("aria-pressed", true);
						menuElement.ol.setAttribute("aria-expanded", true);
					}
					break;
				case 27: // escape
					menuElement.menuButton.setAttribute("aria-pressed", false);
					menuElement.activeLine = -1;
					menuElement.activeRow = 0;
					dieses.active = null;
					menuElement.ol.setAttribute("aria-expanded", false);
					return;
					break;
			}
			if(menuElement.activeLine > -1) menuElement.elements[menuElement.activeLine][menuElement.activeRow].focus();
		}
	}

	var inPath = function(node,toSearch) {
		var path = [];
		while(node && node != document.body) {
			path.push(node);
			node = node.parentNode;
		}
		return path.indexOf(toSearch) > -1;
	}

	var closeActive = function(element) {
		if(dieses.active) {
			if(!inPath(element.target,dieses.menuElements[dieses.active].menu)) {
				if(!dieses.delayclose) dieses.delayclose = window.setTimeout(function(){
					dieses.menuElements[dieses.active].menuButton.setAttribute("aria-pressed", false);
					dieses.menuElements[dieses.active].activeLine = -1;
					dieses.menuElements[dieses.active].activeRow = 0;
					dieses.menuElements[dieses.active].ol.setAttribute("aria-expanded", false);
					dieses.active = null;
					dieses.delayclose = null;
				},500);
			}
		}
	}
	
	var menus = MapHead.querySelectorAll(".JBcheckbox");
	this.menuElements = {};
	this.active = null;
	this.delayclose = null;
	
	for(var i=0;i<menus.length;i++) {
		var menuButton = menus[i].querySelector("button[aria-pressed]");
		var menuElement = {};
		menuButton.addEventListener("click",handleMenuButtonClick,false);
		menuButton.addEventListener("focus",handleMenuButtonFocus,false);
		menuButton.addEventListener("mouseover",handleMenuButtonFocus,false);
		menuElement.menuButton = menuButton;
		menuElement.menu = menus[i]
		menuElement.ol = menus[i].querySelector("ol");
		menuElement.elements = [] ;
		var lis = menus[i].querySelectorAll("li");
		for(var j=0;j<lis.length;j++) {
			menuElement.elements[j] = [];
			menuElement.elements[j][0] = lis[j].querySelector("input");
			menuElement.elements[j][1] = lis[j].querySelector("button");
			menuElement.elements[j][0].tabIndex = -1;
			menuElement.elements[j][1].tabIndex = -1;
			
		}
		menuElement.activeLine = -1;
		menuElement.activeRow = 0;
		menuElement.ol.addEventListener("mouseover",function(){window.clearTimeout(dieses.delayclose); dieses.delayclose = null;},false);
		dieses.menuElements[menuButton.id] = menuElement;
	}
	
	if(JB.GPX2GM.globalMapParameter[dieses.MapId].keydown) {
		document.documentElement.removeEventListener("keydown", JB.GPX2GM.globalMapParameter[dieses.MapId].keydown);
		document.documentElement.removeEventListener("mouseover",JB.GPX2GM.globalMapParameter[dieses.MapId].closeActive);  
		document.documentElement.removeEventListener("touchstart",JB.GPX2GM.globalMapParameter[dieses.MapId].closeActiv);
		document.documentElement.removeEventListener("click",JB.GPX2GM.globalMapParameter[dieses.MapId].closeActive);
	}
 	document.documentElement.addEventListener("keydown", keydown, false);
	JB.GPX2GM.globalMapParameter[dieses.MapId].keydown = keydown;
	document.documentElement.addEventListener("mouseover",closeActive, false);  
	document.documentElement.addEventListener("touchstart",closeActive, false);
	document.documentElement.addEventListener("click",closeActive, false);
		JB.GPX2GM.globalMapParameter[dieses.MapId].closeActive = closeActive;
} // activateCheckBoxGroups

JB.sec2string = function(sec,off) { 
	if(this.parameters.tkorr && this.parameters.mapapi=="gm") {
		if(JB.GPX2GM.parameters.doclang=="en") return (new Date(sec*1000 + off*1000)).toLocaleString('en-EN',{timeZone:'UTC'});
		else if(JB.GPX2GM.parameters.doclang=="fr") return (new Date(sec*1000 + off*1000)).toLocaleString('fr-FR',{timeZone:'UTC'});
		else return (new Date(sec*1000 + off*1000)).toLocaleString('de-DE',{timeZone:'UTC'});
	}
	else {
		if(JB.GPX2GM.parameters.doclang=="en") return (new Date(sec*1000 + off*1000)).toLocaleString('en-EN');
		else if(JB.GPX2GM.parameters.doclang=="fr") return (new Date(sec*1000 + off*1000)).toLocaleString('fr-FR');
		else return (new Date(sec*1000 + off*1000)).toLocaleString('de-DE');
	}
} // JB.sec2string

JB.Zeitstring = function(sekunden) {
	var h=0,m=0,s=Math.floor(sekunden);
	m = Math.floor(s/60);
	s = s%60; if(s<10) s = "0"+s;
	h = Math.floor(m/60)
	m = m%60; if(m<10) m = "0"+m;
	return h+":"+m+":"+s+"h"; 
} // JB.Zeitstring

JB.bounds = function(center_lat,center_lon,radius) {
// https://de.wikipedia.org/wiki/Wegpunkt-Projektion
	var d = radius/6378.137;
	var fak = Math.PI/180;
	var lat = center_lat * fak;
	var lon = center_lon * fak;
	var sind = Math.sin(d);
	var cosd = Math.cos(d);
	var sinlat = Math.sin(lat);
	var coslat = Math.cos(lat);
	var latmin = (Math.asin(sinlat*cosd - coslat*sind))/fak;
	var latmax = (Math.asin(sinlat*cosd + coslat*sind))/fak;
	var lonmin = (lon - Math.asin(sind/coslat))/fak;
	var lonmax = (lon + Math.asin(sind/coslat))/fak;
	return {latmin:latmin,latmax:latmax,lonmin:lonmin,lonmax:lonmax};
} // JB.bounds

JB.Debug_Info = function(id,Infotext,errorflag) {
	if(JB.debuginfo) {
		var dt = (Date.now()-JB.gpxview_Start).toString(10);
		while(dt.length<6) dt = "0"+dt;
		if(typeof(console) != "undefined" && typeof(console.log) == "function") 
			console.log(dt+" Map "+id+": "+Infotext.replace(/<br>/g,"\n").replace(/&nbsp;/g,"  "));
	}
	if(errorflag) {
		if(typeof(console) != "undefined" && typeof(console.error) == "function")
			console.error(id+": "+Infotext);
		// else	
			alert(Infotext);
	}
} // Debug_Info

JB.Wait = function(id,scripte,callback,ct) {
	var Text = "";
	var flag = true; 
	ct = ct || 1;
	for(var i=0;i<scripte.length;i++) {
		var t = JB.Scripte[scripte[i]];
		flag &= t == 2;
		Text += scripte[i] + ": "+ t + ", ";
	}
	JB.Debug_Info(id+" Wait",Text+" flag="+(flag?"true ":"false ")+ct,false);
	if(flag) window.requestAnimationFrame(callback);
	else if(ct<15) window.setTimeout(function() { JB.Wait(id,scripte,callback,ct+1) },100+(1<<ct));
	else JB.Debug_Info(id+" Wait",Text+" nicht geladen.",false);
} // Wait

// lpgpx.js
// Version 2.18
// 30. 12. 2019
// www.j-berkemeier.eu
JB.loadFile = function(file, format, callback) {
	if(!file.fileobject) { // ajax
		JB.loadFile_xml(file, format, callback);
	}
	else { //File API
		JB.LoadFile_local(file, format, callback);
	}
} // loadFile

JB.loadFile_xml = function(file, format, callback) {
	var id = "loadFile_xml";
	var request,url=file.name;
	var result={asciidata:"<gpx></gpx>"};
	if(url.length==0) {
		JB.Debug_Info(id,"Kein Dateiname",false);
		callback(result, 0, null);
		return;
	}
	request = new XMLHttpRequest();
	if(request) {
		JB.Debug_Info(id,"XMLHttpRequest");
		request.addEventListener('load', function(event) {
			if ((request.status >= 200 && request.status < 300) || request.status == 0) {
				if(format=="b") result.binarydata = new Uint8Array(request.response);
				else { result.asciidata = request.responseText }
				JB.Debug_Info(id,"Datei konnte geladen werden, Status: "+request.status+", Datei: "+url,false);
				var lastmodified = request.getResponseHeader("Last-Modified");
				if(lastmodified) {
					lastmodified = new Date(Date.parse(lastmodified));
					var lmstr;
					if(JB.GPX2GM.parameters.doclang=="en")
						lmstr = {d: lastmodified.toLocaleDateString('en-EN'), t: lastmodified.toLocaleTimeString('en-EN')};
					else if(JB.GPX2GM.parameters.doclang=="fr")
						lmstr = {d: lastmodified.toLocaleDateString('fr-FR'), t: lastmodified.toLocaleTimeString('fr-FR')};
					else if(JB.GPX2GM.parameters.doclang=="es")
						lmstr = {d: lastmodified.toLocaleDateString('es-ES'), t: lastmodified.toLocaleTimeString('es-ES')};
					else
						lmstr = {d: lastmodified.toLocaleDateString('de-DE'), t: lastmodified.toLocaleTimeString('de-DE')};
				}
				callback(result, request.status, lmstr);
			} 
			else {
				JB.Debug_Info(id,"Datei konnte nicht geladen werden, Status: "+request.status+", Datei: "+url,true);
				callback(result,request.status,null);
			}
		});
		request.addEventListener('error', function(event) {
			JB.Debug_Info(id,"Datei konnte nicht geladen werden, Status: "+request.status+", Datei: "+url,true);
			callback(result,request.status,null);
		});
		request.open("GET",url);
		if(format=="b") request.responseType = "arraybuffer";
		request.send();
	}
	else {
		JB.Debug_Info(id,"XMLHttpRequest konnte nicht erstellt werden, Datei: "+url,true)
		callback(result,-1,null);
	} 	
} // loadFile_xml

JB.LoadFile_local = function(file, format, callback) {
	var id = "loadFile_local";
	if(typeof(FileReader)=="function" || typeof(FileReader)=="object") {
		var reader = new FileReader();
		var result={};
		reader.readAsDataURL(file.fileobject); 
		reader.onload = function(evt) {
			result.dataurl = evt.target.result;
			if(format=="b") reader.readAsArrayBuffer(file.fileobject);
			else reader.readAsText(file.fileobject); 
			reader.onload = function(evt) {
				if(format=="b") result.binarydata = new Uint8Array(evt.target.result);
				else result.asciidata = evt.target.result; 
				var lastmodified = new Date(file.fileobject.lastModified);
				callback(result,200,{d:lastmodified.toLocaleDateString(),t:lastmodified.toLocaleTimeString()});
			}
			reader.onerror = function(evt) {
				JB.Debug_Info(id,"Datei konnte nicht geladen werden, Status: "+evt.target.error.name+", Datei: "+file.name,true);
				callback({},42,null);
			}
		}
		reader.onerror = function(evt) {
			JB.Debug_Info(id,"Datei konnte nicht geladen werden, Status: "+evt.target.error.name+", Datei: "+file.name,true);
			callback({},42,null);
		}
	}
	else {
		JB.Debug_Info(id,"FileReader wird vom Browser nicht unterst\u00fctzt.",true);
		JB.Debug_Info(id,"FileReader = "+FileReader+"; typeof(FileReader) = "+typeof(FileReader),false);
	}	
} // JB.LoadFile_local

JB.entf = (function() {
	var fak = Math.PI/180,ls,le,hs,he,be,sinbs,sinbe,cosbs,cosbe,dh,arg,e;
	var si = Math.sin, co = Math.cos, ac = Math.acos, sq = Math.sqrt;
	function entf_o() {
		this.init = function(b,l,h) {
			le = l*fak;
			be = b*fak;
			he = h;
			sinbe = si(be);
			cosbe = co(be);
		}
		this.rechne = function(b,l,h) {
			ls = le ;
			le = l*fak;
			hs = he ;
			he = h;
			be = b*fak;
			dh = (h - hs)/1000;
			sinbs = sinbe;
			cosbs = cosbe;
			sinbe = si(be);
			cosbe = co(be);
			arg = sinbs*sinbe + cosbs*cosbe*co(ls-le);
			if(arg>1) arg = 1; // arg kann durch die letzte Stelle schon mal >1 oder <-1 sein
			else if(arg<-1) arg = -1;
			e = ac ( arg ) * 6378.137;
			if(dh!=0) e = sq(e*e+dh*dh);
			return e;
		}
	}
	return new entf_o();
})() // entf

JB.lpgpx = function(fns,id,callback) {
	
	var dieses = this;

	function xmlParse(str) {
		JB.Debug_Info(id,"xmlParse -",false);
		if(str && typeof DOMParser != 'undefined') {
			str = str.replace(/>\s+</g,"><");
			str = str.replace(/gpxtpx:|gpxx:|ns3:/g,"");
			str = str.replace(/cadence>/g,"cad>");
			str = str.replace(/heartrate>/g,"hr>");
			return (new DOMParser()).parseFromString(str, 'text/xml');
		}
		JB.Debug_Info(id,"xml konnte nicht geparsed werde!",false);
		return document.createElement("div");
	} // xmlParse
	
	function rauf_runter(a) {
		var l=a.length;
		if(l<2) return { rauf:0, runter:0 } ; 
		var rauf = 0;
		var runter = 0;
		var h = a[0].hs;
		a[0].rauf = rauf;
		a[0].runter = runter;
		var hm,dh;
		for(var i=1;i<l;i++) {
			hm = h;
			h = a[i].hs;
			dh = h - hm;
			if(dh>0) rauf += dh;
			else runter -= dh;
			a[i].rauf = rauf;
			a[i].runter = runter;
		}
		var korrektur = ( (a[a.length-1].h-a[0].h) - (rauf-runter) ) / 2;
		rauf   += korrektur;
		runter -= korrektur;
		rauf = Math.round(rauf);
		runter = Math.round(runter);
		a[a.length-1].rauf = rauf;
		a[a.length-1].runter = runter;		
		return { rauf:rauf, runter:runter } ;    
	} // rauf_runter
	
	function getTag_qs(ele,tagname,defval,child) {
		var tag, val=defval;
		if(child) tag = ele.querySelector(':scope > '+tagname);
		else tag = ele.querySelector(tagname);
		if( tag && tag.firstChild ) { val = tag.firstChild.data; }
		return val;
	} // getTag_qs
	
	function getLink_qs(ele,defval,child) {
		var tag, val=defval;
		if(child) tag = ele.querySelector(':scope > link');
		else tag = ele.querySelector("link");
		if( tag ) {
				if( tag.hasAttribute("href") ) { val = tag.getAttribute("href"); }
				else if( tag.firstChild ) { val = tag.firstChild.data; }
		} 
		return val;
	} // getLink_qs

	function getTag_ge(ele,tagname,defval,child) {
		var tag = ele.getElementsByTagName(tagname), val=defval, tag0;
		if( tag && tag.length ) {
			tag0 = tag[0];
			if( tag0.firstChild && (child?(tag0.parentNode==ele):true) )
				val = tag0.firstChild.data;
		}
		return val;
	} // getTag_ge
	
	function getLink_ge(ele,defval,child) {
		var tag = ele.getElementsByTagName("link"), val=defval, tag0;
		if( tag && tag.length ) {
			tag0 = tag[0];
			if( (child?(tag0.parentNode==ele):true) ) {
				if( tag0.hasAttribute("href") ) { val = tag0.getAttribute("href"); }
				else if( tag0.firstChild ) { val = tag0.firstChild.data; }
			}
		} 
		return val;
	} // getLink_ge

	var getTag,getLink;
	(function(){
		var neu=false;
		try {
			var t1 = document.body.appendChild(document.createElement("div"));
			var t2 = document.body.querySelector(':scope > div');
			neu = true;	
			document.body.removeChild(t1);
		}
		catch(e) {}
		if(neu) {
			getTag = getTag_qs;
			getLink = getLink_qs;
		}
		else {
			getTag = getTag_ge;
			getLink = getLink_ge;
		}
	})();
	
	function utc2sec(utcdate) {
		return Date.parse(utcdate)/1000;
	} // utc2sec
	
	function smooth(a,x,y,ys,range) {
		var fak,faksum,sum,xi,xmin,xmax,xj,i,j,ai,aj,ti;
		var l = a.length;
		var t = []; 
		for(i=0;i<l;i++) { 
			ti = {}; 
			ai = a[i];
			ti[ys] = ai[y]; 
			for(var o in ai) ti[o] = ai[o]; 
			t[i] = ti;
		}
		var x0 = a[0][x];
		var xl = a[l-1][x];
		range /= 2000;
		if(range>(xl-x0)/4 || range==0) return t;
		var glattpar = 1.0; // 1.3;
		var dx;
		for(i=0;i<l;i++) {
			ai = a[i];
			xi = ai[x];
			xmin = xi - range;
			xmax = xi + range;
			sum = ai[y];
			faksum = 1;
			j = i - 1;
			if(j>=0) {
				aj = a[j];
				xj = aj[x];
				while(xj>xmin) {
					dx = -(xj - xi)/range;
					fak = 1 - glattpar*dx*dx;
					sum += aj[y]*fak;
					faksum += fak;
					j--;
					if(j<0) break;
					aj = a[j];
					xj = aj[x];
				}
			}
			j = i + 1;
			if(j<l) {
				aj = a[j];
				xj = aj[x];
				while(xj<xmax) {
					dx = (xj - xi)/range;
					fak = 1 - glattpar*dx*dx;
					sum += aj[y]*fak;
					faksum += fak;
					j++;
					if(j>=l) break;
					aj = a[j];
					xj = aj[x];
				}
			}
			t[i][ys] = sum/faksum;
		}
		return t;
	} // smooth

	function diff(a,x,y,d,fak) {
		var l=a.length,l1=l-1;
		if(l<3) { for(var i=0;i<l;i++) a[i][d] = 0; return a; }
		var dx,dy;
		dx = a[1][x]-a[0][x];
		dy = a[1][y]-a[0][y];
		if(dx==0) a[0][d] = 0;
		else      a[0][d] = fak*dy/dx;
		for(var i=1;i<l1;i++) {
			dx = a[i+1][x]-a[i-1][x];
			dy = a[i+1][y]-a[i-1][y];
			if(dx==0) a[i][d] = a[i-1][d];
			else      a[i][d] = fak*dy/dx;
		}
		dx = a[l1-1][x]-a[l1][x];
		dy = a[l1-1][y]-a[l1][y] ;
		if(dx==0) a[l1][d] = a[l1-1][d];
		else      a[l1][d] = fak*dy/dx;
		return a;
	} // diff

	function korr(daten,y) {
		var npt = daten.length;
		var anzfehl=0,nf=false,fehlst_n,fehlst=[],kflag = false;
		for(var i=0;i<npt;i++) {
			if(daten[i][y] == "nf") {              // Fehlstelle?
				anzfehl ++;                         // Zählen
				if(!nf) {                           // erste Fehlstelle im Block
					fehlst_n = {s:i,e:npt-1};
					nf = true;
				}
			}
			else {
				if(nf) {                              // Erster Wert nach Fehlstelle?
					fehlst_n.e = i;                     // Ende Fehlstellenblock
					fehlst.push(fehlst_n);
					nf = false;
				}
			}
		}
		if(nf) {                                // Letzer Punkt im Fehlstellenblock
			fehlst_n.e = i;                       // Ende Fehlstellenblock
			fehlst.push(fehlst_n);
		}
		JB.Debug_Info(id,y+": "+anzfehl+" Fehlende Werte in "+fehlst.length+" Bl\u00F6cken",false);  
		for(var i=0;i<fehlst.length;i++) 
			JB.Debug_Info(id,"Fehlerblock Nr. "+i+":"+fehlst[i].s+" - "+fehlst[i].e,false);   
		if(anzfehl/npt < 0.3) { // weniger als 30% Fehlstellen
			kflag = true;
			for(var i=0;i<fehlst.length;i++) {
				var s = fehlst[i].s, e = fehlst[i].e;
				if(s==0)
					for(var j=s;j<e;j++) daten[j][y] = daten[e][y];
				else if(e==npt)
					for(var j=s;j<e;j++) daten[j][y] = daten[s-1][y];
				else 
					for(var j=s;j<e;j++) daten[j][y] = daten[s-1][y] + (daten[e][y]-daten[s-1][y])*(j-s)/(e-s);
			}
		}
		return kflag;
	} // korr
	
	function unwraplon(daten,usegpxbounds) {
		var delta;
		var korrektur = 0;
		var lon = []; 
		var lonmin, lonmax;
		var lonminmax = function(lon) {
			if(lon < lonmin) lonmin = lon;
			if(lon > lonmax) lonmax = lon;
		}
		for(var j=0;j<daten.length;j++) {
			lon[j] = [];
			for(var i=0;i<daten[j].daten.length;i++) { 
				lon[j][i] = daten[j].daten[i].lon;
			}
		}
		for(var j=0;j<daten.length;j++) {
			if(j>0) {
				delta = lon[j][0] - lon[j-1][lon[j-1].length-1];
				if(delta > 180) korrektur -= 360;
				else if(delta < -180) korrektur += 360;
				daten[j].daten[0].lon += korrektur;
			}
			for(var i=1;i<daten[j].daten.length;i++) { 
				delta = lon[j][i] - lon[j][i-1];
				if(delta > 180) korrektur -= 360;
				else if(delta < -180) korrektur += 360;
				daten[j].daten[i].lon += korrektur;
			}
		}
		for(var j=0;j<daten.length;j++) {
			lonmin = 10000; lonmax = -10000;
			for(var i=0;i<daten[j].daten.length;i++) { 
				if(korrektur > 0) daten[j].daten[i].lon -= korrektur;
				if(!usegpxbounds) lonminmax(daten[j].daten[i].lon);
			}
			if(!usegpxbounds) {
				daten[j].lonmin = lonmin;
				daten[j].lonmax = lonmax;
			}
		}
	} // unwraplon

	var fnr = 0;
	var t0 = 0;
	var gpxdaten = {tracks:{},routen:{},wegpunkte:{}};
	var tnr, rnr, latmin, latmax, lonmin ,lonmax;

	function parseGPX(xml,gpxdaten,id,fnr) {	
		JB.Debug_Info(id,"parseGPX",false);
		var usegpxbounds=false;
		if(dieses.parameters.usegpxbounds) {
			var gpxmetadata = xml.documentElement.getElementsByTagName("metadata"); 
			if(gpxmetadata.length) var gpxbounds = gpxmetadata[0].getElementsByTagName("bounds");
			if(gpxbounds && gpxbounds.length) usegpxbounds = true; 
		}
		dieses.parameters.usegpxbounds = usegpxbounds;
		if(fnr == 0) {
			gpxdaten.tracks.laenge = 0;
			gpxdaten.tracks.rauf = 0;
			gpxdaten.tracks.runter = 0;
			gpxdaten.tracks.hflag = gpxdaten.tracks.tflag = gpxdaten.tracks.vflag = gpxdaten.tracks.hrflag = gpxdaten.tracks.cadflag = false;
			gpxdaten.tracks.hflagall = gpxdaten.tracks.tflagall = gpxdaten.tracks.vflagall = gpxdaten.tracks.hrflagall = gpxdaten.tracks.cadflagall = true;
			gpxdaten.tracks.latmin=1000;gpxdaten.tracks.latmax=-1000;
			gpxdaten.tracks.lonmin=1000;gpxdaten.tracks.lonmax=-1000;
			gpxdaten.tracks.track = [];
			gpxdaten.routen.laenge = 0;
			gpxdaten.routen.route = [];
			gpxdaten.routen.latmin=1000;gpxdaten.routen.latmax=-1000;
			gpxdaten.routen.lonmin=1000;gpxdaten.routen.lonmax=-1000;
			gpxdaten.wegpunkte.wegpunkt = [];
			gpxdaten.wegpunkte.latmin=1000;gpxdaten.wegpunkte.latmax=-1000;
			gpxdaten.wegpunkte.lonmin=1000;gpxdaten.wegpunkte.lonmax=-1000;
			tnr = rnr = -1;
			if(usegpxbounds) {
				latmin = parseFloat(gpxbounds[0].getAttribute("minlat"));
				latmax = parseFloat(gpxbounds[0].getAttribute("maxlat"));
				lonmin = parseFloat(gpxbounds[0].getAttribute("minlon"));
				lonmax = parseFloat(gpxbounds[0].getAttribute("maxlon"));
			}
			else {
				latmin=1000;latmax=-1000;lonmin=1000;lonmax=-1000;
			}
		}
		if(usegpxbounds && fnr!=0) {
			var t = parseFloat(gpxbounds[0].getAttribute("minlat"));
			if(t<latmin) latmin = t;
			t = parseFloat(gpxbounds[0].getAttribute("maxlat"));
			if(t>latmax) latmax = t;
			t = parseFloat(gpxbounds[0].getAttribute("minlon"));
			if(t<lonmin) lonmin = t;
			t = parseFloat(gpxbounds[0].getAttribute("maxlon"));
			if(t>lonmax) lonmax = t;
		}
		
		// Tracks 
		var trk = xml.documentElement.getElementsByTagName("trk"); 
		JB.Debug_Info(id,trk.length +" Tracks gefunden",false);   
		if(dieses.parameters.gpxtracks) for(var k=0;k<trk.length;k++) { 
			var trkk = trk[k];
			var trkpts = trkk.getElementsByTagName("trkpt"); // Trackpunkte
			var trkptslen = trkpts.length;
			if(trkptslen>1) {
				tnr++; 
				var tracki = { laenge:0, rauf:0, runter:0, t0:0, tzoff:0, vmitt:0, vmittwop:0, fnr:fnr};
				tracki.name = getTag(trkk,"name","Track "+k,true);
				tracki.cmt = getTag(trkk,"cmt","",true);
				tracki.desc = getTag(trkk,"desc","",true);
				tracki.link = getLink(trkk,"",true);
				tracki.farbe = dieses.parameters.tcols[tnr%dieses.parameters.tcols.length];
				tracki.latmin=1000;tracki.latmax=-1000;
				tracki.lonmin=1000;tracki.lonmax=-1000;
				if(dieses.parameters.displaycolor) {
					var ext = trkk.getElementsByTagName("extensions");
					if(ext.length) {
						tracki.farbe = getTag(ext[0],"DisplayColor",dieses.parameters.tcols[tnr%dieses.parameters.tcols.length],false);
						if(tracki.farbe.toLowerCase()=="darkyellow") tracki.farbe = "#8b8b00";
					}
				}
				var daten = [];
				var hflag=true,tflag=true,vflag=dieses.parameters.readspeed,hrflag=true,cadflag=true,cadfound=false,atempflag=true;
				var h,t,v,hr,cad,atemp,tabs,tmp;
				JB.Debug_Info(id,trkptslen+" Trackpunkte in Track "+k+" gefunden",false);
				for(var i=0;i<trkptslen;i++) { // Trackdaten erfassen
					var trkptsi = trkpts[i];
					var lat = parseFloat(trkptsi.getAttribute("lat"));
					var lon = parseFloat(trkptsi.getAttribute("lon"));
					if(!usegpxbounds) {
						if(lat<latmin) latmin=lat; if(lat>latmax) latmax=lat;
						if(lon<lonmin) lonmin=lon; if(lon>lonmax) lonmax=lon;
					}
					if(lat<tracki.latmin) tracki.latmin=lat; if(lat>tracki.latmax) tracki.latmax=lat;
					if(lon<tracki.lonmin) tracki.lonmin=lon; if(lon>tracki.lonmax) tracki.lonmax=lon;
					h = getTag(trkptsi,"ele","nf",false);
					if(h=="nf") hflag = false;
					else h = parseFloat(h.replace(",",".")) * dieses.parameters.hfaktor;
					tmp = getTag(trkptsi,"time","nf",false);
					if(tmp!="nf") { 
						tabs = utc2sec(tmp)/3600;
						if( i==0 ) {
							tracki.t0 = tabs;
								t = 0; 
								t0 = tracki.t0;
						}
						else 
							t = tabs - t0;
					}
					else {
						tflag = false;
						tabs = t = 0;
					}
					if(vflag) {
						if((tmp=getTag(trkptsi,"speed","nf",false)) != "nf")
							v = parseFloat(tmp) * dieses.parameters.speedfaktor;
						else {
							v = 0;
							vflag = false;
						}
					}
					atemp = getTag(trkptsi,"atemp","nf",false);
					if(atemp=="nf") atempflag = false;
					else atemp = parseFloat(atemp) * dieses.parameters.tfaktor + dieses.parameters.toffset;
					hr = getTag(trkptsi,"hr","nf",false);
					if(hr=="nf") hrflag = false;
					else hr = parseFloat(hr);
					if(cadflag) {
						if((tmp=getTag(trkptsi,"cad","nf",false)) != "nf") {
							cad = parseFloat(tmp);
							cadfound = true;
						}
						else {
							cad = 0;
						}
					}
					var dateni = {lat:lat,lon:lon,t:t,h:h,v:v,hr:hr,cad:cad,tabs:tabs,atemp:atemp};
					daten.push(dateni);
				} // Trackdaten erfassen
				if(!hflag) hflag = korr(daten,"h"); // Höhen korrigieren
				if(!hrflag) hrflag = korr(daten,"hr"); // Puls korrigieren
				if(!atempflag) atempflag = korr(daten,"atemp"); // Temperatur korrigieren
				cadflag &= cadfound;
				var tracklen = 0;
				daten[0].x = tracklen;
				daten[0].dx = 0;
				var dateni,dx; 
				dateni = daten[0];
				JB.entf.init(dateni.lat,dateni.lon,0.0) ;
				for(var i=1;i<trkptslen;i++) {
					dateni = daten[i];
					dx = JB.entf.rechne(dateni.lat,dateni.lon,0.0) * dieses.parameters.wfaktor;
					tracklen += dx;
					daten[i].x = tracklen;
					daten[i].dx = dx;
				}
				if(hflag) {
					daten = smooth(daten,"x","h","hs",dieses.parameters.hglattlaen);
					var rr = rauf_runter(daten);
					if(dieses.parameters.hglatt) 
						for(var i=0;i<trkptslen;i++) daten[i].h = daten[i].hs;
					daten = diff(daten,"x","hs","s",0.1*dieses.parameters.sfaktor);
					daten = smooth(daten,"x","s","s",dieses.parameters.hglattlaen);
					JB.Debug_Info(id,"Rauf: "+rr.rauf+"   Runter: "+rr.runter,false);
					tracki.rauf = rr.rauf;
					tracki.runter = rr.runter;
					gpxdaten.tracks.rauf += rr.rauf;      
					gpxdaten.tracks.runter += rr.runter;     
				}
				if(hflag && dieses.parameters.laengen3d) {
					tracklen = 0;
					dateni = daten[0];
					JB.entf.init(dateni.lat,dateni.lon,dateni.hs) ;
					for(var i=1;i<trkptslen;i++) {
				  	dateni = daten[i];
						dx = JB.entf.rechne(dateni.lat,dateni.lon,dateni.hs) * dieses.parameters.wfaktor;
						tracklen += dx;
						daten[i].x = tracklen;
						daten[i].dx = dx;
					}
				}
				if(tflag && !vflag) {
					if(dieses.parameters.vglatt) {
						daten = smooth(daten,"t","x","xs",dieses.parameters.vglattlaen);
						daten = diff(daten,"t","xs","v",1*dieses.parameters.vfaktor);
						daten = smooth(daten,"x","v","v",dieses.parameters.vglattlaen);
					}
					else {
						daten = diff(daten,"t","x","v",1*dieses.parameters.vfaktor);
					}
				}
				if(!hrflag) for(var i=0;i<daten.length;i++) delete daten[i].hr;
				if(!atempflag) for(var i=0;i<daten.length;i++) delete daten[i].atemp;
				if(!cadflag) for(var i=0;i<daten.length;i++) delete daten[i].cad;
				JB.Debug_Info(id,""+(hflag?"":"Keine ")+"H\u00F6hendaten gefunden",false);
				JB.Debug_Info(id,""+(tflag?"":"Keine ")+"Zeitdaten gefunden",false);
				JB.Debug_Info(id,""+(vflag?"":"Keine ")+"Geschwindigkeitsdaten gefunden",false);
				JB.Debug_Info(id,""+(hrflag?"":"Keine ")+"Herzfrequenzdaten gefunden",false);
				JB.Debug_Info(id,""+(cadflag?"":"Keine ")+"Cadenzdaten gefunden",false);
				JB.Debug_Info(id,""+(atempflag?"":"Keine ")+"Temperaturdaten gefunden",false);
				if(tflag) {
					tracki.tges = daten[daten.length-1].t-daten[0].t;
					tracki.vmitt = tracklen/(daten[daten.length-1].t-daten[0].t); // *3600;
					tracki.vmitt = Math.round(tracki.vmitt*10)/10;
					if(dieses.parameters.shtrvmittwob || dieses.parameters.shtrvmittpacewob || dieses.parameters.shtrtgeswob || dieses.parameters.shtrtwob) {
						var tpause = 0;
						daten[0].twob = daten[0].t;
						for(var i=0;i<daten.length-1;i++) {
							if(daten[i].v < dieses.parameters.movevmin) tpause += daten[i+1].t-daten[i].t ;
							daten[i+1].twob = daten[i+1].t - tpause;
						}
						tracki.vmittwob = tracklen/(daten[daten.length-1].t-daten[0].t-tpause)
						tracki.vmittwob = Math.round(tracki.vmittwob*10)/10;
						tracki.tgeswob = tracki.tges - tpause;
					}
				}
				tracki.daten = daten;
				tracki.laenge = Math.round(tracklen*10)/10;
				tracki.hflag = hflag;
				tracki.tflag = tflag;
				tracki.vflag = vflag;
				tracki.hrflag = hrflag;
				tracki.atempflag = atempflag;
				tracki.cadflag = cadflag;
				if(dieses.parameters.unwraplon) unwraplon([tracki],usegpxbounds);
				gpxdaten.tracks.hflag |= hflag;
				gpxdaten.tracks.tflag |= tflag;
				gpxdaten.tracks.vflag |= vflag;
				gpxdaten.tracks.hrflag |= hrflag;
				gpxdaten.tracks.atempflag |= atempflag;
				gpxdaten.tracks.cadflag |= cadflag;
				gpxdaten.tracks.hflagall &= hflag;
				gpxdaten.tracks.tflagall &= tflag;
				gpxdaten.tracks.vflagall &= vflag;
				gpxdaten.tracks.hrflagall &= hrflag;
				gpxdaten.tracks.atempflagall &= atempflag;
				gpxdaten.tracks.cadflagall &= cadflag;
				gpxdaten.tracks.track.push(tracki);
				gpxdaten.tracks.laenge += Math.round(tracklen*10)/10;
				if(tracki.latmin < gpxdaten.tracks.latmin) gpxdaten.tracks.latmin = tracki.latmin; 
				if(tracki.lonmin < gpxdaten.tracks.lonmin) gpxdaten.tracks.lonmin = tracki.lonmin; 
				if(tracki.latmax > gpxdaten.tracks.latmax) gpxdaten.tracks.latmax = tracki.latmax; 
				if(tracki.lonmax > gpxdaten.tracks.lonmax) gpxdaten.tracks.lonmax = tracki.lonmax;
			}
		}
		if(dieses.parameters.unwraplon) {
			if(dieses.parameters.tracks_verbinden ) unwraplon(gpxdaten.tracks.track,usegpxbounds);
			if(!usegpxbounds)	{
				lonmin=1000;lonmax=-1000;
				for(var k=0;k<gpxdaten.tracks.track.length;k++) {
					var tracki = gpxdaten.tracks.track[k];
					if(tracki.lonmin < lonmin) lonmin = tracki.lonmin;
					if(tracki.lonmax > lonmax) lonmax = tracki.lonmax;
				}
			}
		}
		gpxdaten.tracks.lonmin = lonmin; 
		gpxdaten.tracks.lonmax = lonmax; 
		gpxdaten.tracks.anzahl = gpxdaten.tracks.track.length;
		gpxdaten.tracks.t0 = gpxdaten.tracks.anzahl ? gpxdaten.tracks.track[0].t0 : 0;
		
		// Routen
		var rte = xml.documentElement.getElementsByTagName("rte"); 
		JB.Debug_Info(id,rte.length +" Routen gefunden",false);
		if(dieses.parameters.gpxrouten) for(var j=0;j<rte.length;j++) {
			rnr++;
			var rtej = rte[j];
			var rtepts = rtej.getElementsByTagName("rtept");
			JB.Debug_Info(id,rtepts.length +" Zwischenziele gefunden",false);
			var routei = { laenge:0, farbe:dieses.parameters.rcols[rnr%dieses.parameters.rcols.length] };
			var routlen = 0;
			routei.name = getTag(rtej,"name","Route "+j,true);
			routei.cmt = getTag(rtej,"cmt","",true);
			routei.desc = getTag(rtej,"desc","",true);
			routei.link = getLink(rtej,"",true);
			routei.latmin=1000;routei.latmax=-1000;
			routei.lonmin=1000;routei.lonmax=-1000;
			if(dieses.parameters.displaycolor) {
				var ext = rtej.getElementsByTagName("extensions");
				if(ext.length) {
					routei.farbe = getTag(ext[0],"DisplayColor",dieses.parameters.rcols[rnr%dieses.parameters.rcols.length],false);
					if(routei.farbe.toLowerCase()=="darkyellow") routei.farbe = "#8b8b00";
				}
			}
			var daten = [];
			for(var i=0;i<rtepts.length;i++) { // Zwischenziele
				var rteptsi = rtepts[i];
				var lat = parseFloat(rteptsi.getAttribute("lat"));
				var lon = parseFloat(rteptsi.getAttribute("lon"));
				if(dieses.parameters.unfoldlon) lon = unfold(lon);
				if(i==0) JB.entf.init(lat,lon,0.0) ;
				else     routlen += JB.entf.rechne(lat,lon,0.0)*dieses.parameters.wfaktor;      
				if(!usegpxbounds) {
					if(lat<latmin) latmin=lat; if(lat>latmax) latmax=lat;
					if(lon<lonmin) lonmin=lon; if(lon>lonmax) lonmax=lon;
				}
				if(lat<routei.latmin) routei.latmin=lat; if(lat>routei.latmax) routei.latmax=lat;
				if(lon<routei.lonmin) routei.lonmin=lon; if(lon>routei.lonmax) routei.lonmax=lon;
				daten.push({lat:lat,lon:lon,x:routlen});
				var rpts = rteptsi.getElementsByTagName("rpt"); // Routenpunkte
				if(rpts.length>0) JB.Debug_Info(id,rpts.length +" Routenpunkte (Garmin) gefunden",false);
				for(var k=0;k<rpts.length;k++) {
					var rptsk = rpts[k];
					var lat = parseFloat(rptsk.getAttribute("lat"));
					var lon = parseFloat(rptsk.getAttribute("lon"));
					if(dieses.parameters.unfoldlon) lon = unfold(lon);
					routlen += JB.entf.rechne(lat,lon,0.0)*dieses.parameters.wfaktor;
					if(!usegpxbounds) {
						if(lat<latmin) latmin=lat; if(lat>latmax) latmax=lat;
						if(lon<lonmin) lonmin=lon; if(lon>lonmax) lonmax=lon;
					}
					if(lat<routei.latmin) routei.latmin=lat; if(lat>routei.latmax) routei.latmax=lat;
					if(lon<routei.lonmin) routei.lonmin=lon; if(lon>routei.lonmax) routei.lonmax=lon;
					daten.push({lat:lat,lon:lon,x:routlen});
				}
			}
			routei.daten = daten;
			routei.laenge = Math.round(routlen*10)/10;
			// if(dieses.parameters.unwraplon) unwraplon([routei],usegpxbounds);
			gpxdaten.routen.route.push(routei);
			gpxdaten.routen.laenge += Math.round(routlen*10)/10;
			if(routei.latmin < gpxdaten.routen.latmin) gpxdaten.routen.latmin = routei.latmin; 
			if(routei.lonmin < gpxdaten.routen.lonmin) gpxdaten.routen.lonmin = routei.lonmin; 
			if(routei.latmax > gpxdaten.routen.latmax) gpxdaten.routen.latmax = routei.latmax; 
			if(routei.lonmax > gpxdaten.routen.lonmax) gpxdaten.routen.lonmax = routei.lonmax; 
		}
		gpxdaten.routen.anzahl = gpxdaten.routen.route.length;
		
		// Waypoints
		var wpts = xml.documentElement.getElementsByTagName("wpt"); 
		JB.Debug_Info(id,wpts.length +" Wegpunkte gefunden",false);
		if(dieses.parameters.gpxwegpunkte) for(var i=0;i<wpts.length;i++) { // Wegpunktdaten
			var wpt = wpts[i];
			var lat = parseFloat(wpt.getAttribute("lat"));
			var lon = parseFloat(wpt.getAttribute("lon"));
			if(dieses.parameters.unfoldlon) lon = unfold(lon);
			if(!usegpxbounds) {
				if(lat<latmin) latmin=lat; if(lat>latmax) latmax=lat;
				if(lon<lonmin) lonmin=lon; if(lon>lonmax) lonmax=lon;
			}
			if(lat<gpxdaten.wegpunkte.latmin) gpxdaten.wegpunkte.latmin=lat; if(lat>gpxdaten.wegpunkte.latmax) gpxdaten.wegpunkte.latmax=lat;
			if(lon<gpxdaten.wegpunkte.lonmin) gpxdaten.wegpunkte.lonmin=lon; if(lon>gpxdaten.wegpunkte.lonmax) gpxdaten.wegpunkte.lonmax=lon;
			var waypoint = {};
			waypoint.lat = lat;
			waypoint.lon = lon;
			waypoint.name = getTag(wpt,"name","",false);
			waypoint.cmt = getTag(wpt,"cmt","",false);
			waypoint.desc = getTag(wpt,"desc","",false);
			waypoint.link = getLink(wpt,"",false);
			waypoint.sym = getTag(wpt,"sym","default",false);
			waypoint.time = utc2sec(getTag(wpt,"time","1980-01-01T12:00:00Z",false));
			gpxdaten.wegpunkte.wegpunkt.push(waypoint);
		}
		gpxdaten.wegpunkte.anzahl = gpxdaten.wegpunkte.wegpunkt.length;
		gpxdaten.latmin = latmin;
		gpxdaten.latmax = latmax;
		gpxdaten.lonmin = lonmin;
		gpxdaten.lonmax = lonmax;
		return gpxdaten
	} // parseGPX

	function lpgpxResponse(response,status,filelastmod) {
		if( (status >= 200 && status < 300) || status == 0) {
			gpxdaten = parseGPX(xmlParse(response.asciidata),gpxdaten,id,fnr);
			fns[fnr].filelastmod = filelastmod;
		}
		else 
			JB.Debug_Info(id,fns[fnr].name+" konnte nicht gelesen werden",true);
		if(fns[++fnr]) {
			JB.Debug_Info(id,fns[fnr].name,false);
			JB.loadFile(fns[fnr],"a",lpgpxResponse);
		}
		else {
			callback.call(dieses,gpxdaten);
		}
	} // lpgpxResponse
	JB.Debug_Info(id,fns[fnr].name,false);
	window.requestAnimationFrame(function() { JB.loadFile(fns[fnr],"a",lpgpxResponse); });
} // JB.lpgpx
// Ende lpgpx.js

JB.LoadScript = function(url,callback) {
	var scr = document.createElement('script');
	scr.type = "text/javascript";
	scr.async = "async";
	if(typeof(callback)=="function") {
		scr.onloadDone = false;
		scr.onload = function() { 
			if ( !scr.onloadDone ) {
				scr.onloadDone = true;
				JB.Debug_Info(url,"loaded",false);
				callback(); 
			}
		};
		scr.onreadystatechange = function() { 
			if ( ( "loaded" === scr.readyState || "complete" === scr.readyState ) && !scr.onloadDone ) {
				scr.onloadDone = true; 
				JB.Debug_Info(url,"ready",false);
				callback();
			}
		}
	}
	scr.onerror = function() {
		JB.Debug_Info(url,"Konnte nicht geladen werden.",false);
	}
	scr.src = url;
	document.getElementsByTagName('head')[0].appendChild(scr);
} // LoadScript

JB.LoadCSS = function(url) {
	var l = document.createElement("link");
	l.type = "text/css";
	l.rel = "stylesheet";
	l.href = url;
	document.getElementsByTagName("head")[0].appendChild(l);
	JB.Debug_Info(url,"load",false);
	l.onerror = function() {
		JB.Debug_Info(url,"Konnte nicht geladen werden.",false);
	}
} // LoadCSS

JB.onresize = function(ele,callback,run) {
	var w = ele.offsetWidth;
	var h = ele.offsetHeight;
	if(run) window.setTimeout(function() {callback(w,h)});
	return window.setInterval(function() {
		var ww = ele.offsetWidth;
		var hh = ele.offsetHeight;
		if(w != ww || h != hh) {
			w = ww;
			h = hh;
			callback(w,h);
		}
	},200);
} // onresize

JB.offresize = function(id) {
	window.clearInterval(id);
} // offresize

JB.farbtafel = function(n) {
	var gauss = function(a,hwb,pos,x) {
		var t = (x-pos)/hwb;
		return Math.round(a*Math.exp(-t*t));
	}
	var tafel = [],r,g,b,i,n2=n*n;
	for(i=0;i<n;i++) {
		b = gauss(255,n/3,0.25*n,i); // + gauss(220,n/15,1.00*n,i);
		g = gauss(255,n/3,0.50*n,i); // + gauss(220,n/15,1.00*n,i);
		r = gauss(255,n/3,0.75*n,i); // + gauss(200,n/15,1.00*n,i);
		r = Math.min(255,r);
		g = Math.min(255,g);
		b = Math.min(255,b);
		tafel.push("rgb("+r+","+g+","+b+")");
	}
	return tafel;
} // farbtafel

JB.farbtafel_bipolar = function() {
	var tafel = [],r,g,b,i;
	for(g=128;g<255;g++) tafel.push("rgb("+0+","+g+","+0+")");
	for(i=0;i<255;i++) {
		g = 255;
		r = i;
		b = 0;//i;
		tafel.push("rgb("+r+","+g+","+b+")");
	}
	for(i=0;i<255;i++) {
		r = 255;
		g = 255 - i;
		b = 0;//255 - i;
		tafel.push("rgb("+r+","+g+","+b+")");
	}
	for(r=255;r>128;r--) tafel.push("rgb("+r+","+0+","+0+")");
	return tafel;
} // farbtafel_bipolar

JB.addClass = function(classname,element) {
	if(element.classList) element.classList.add(classname);
	else {
		var cn = element.className;
		if(cn.indexOf(classname)!=-1) {
			return;
		}
		if(cn!='') {
			classname = ' '+classname;
		}
		element.className = cn+classname;
	}
} // addClass
			
JB.removeClass = function(classname,element) {
	if(element.classList) element.classList.remove(classname);
	else {
		var cn = element.className;
		var rxp = new RegExp("\\s?\\b"+classname+"\\b","g");
		cn = cn.replace(rxp,'');
		element.className = cn;
	}
}	// removeClass		

JB.getRect = function (o) {
	var r = { top:0, left:0, width:0, height:0 };
	if(!o) return r;
	else if(typeof o == 'string' ) o = document.getElementById(o);
	if(typeof o != 'object') return r;
	if(typeof o.offsetTop != 'undefined') {
		r.height = o.offsetHeight;
		r.width = o.offsetWidth;
		r.left = r.top = 0;
		while (o && o.tagName != 'BODY') {
			r.top  += parseInt( o.offsetTop );
			r.left += parseInt( o.offsetLeft );
			o = o.offsetParent;
		}
	}
	return r;
} // getRect

JB.em_px = function() {  
	var node = document.createElement('span');  
	node.style.padding = node.style.margin = 0;  
	node.style.border = 'none';  
	node.style.position = 'absolute';  
	node.style.width = '1em';  
	document.body.appendChild(node);  
	var em_px = node.offsetWidth;
	document.body.removeChild(node);  
	//alert(em_px);  
	return em_px;
}  

JB.openurl = function(url) {
	if(url.search("~")==0) window.location.href = url.substr(1);
	else if(JB.GPX2GM.parameters.linktarget == "") window.location.href = url;
	else if(JB.GPX2GM.parameters.linktarget == "popup") window.open(url,"",JB.GPX2GM.parameters.popup_Pars);
	else {
		var a = document.createElement("a");
		a.href = url;
		a.target = JB.GPX2GM.parameters.linktarget;
		document.body.appendChild(a);
		a.click();
		a.parentNode.removeChild(a);
	}
} // openurl

JB.gmcb = function() {
	JB.Scripte.maplib = 2;
	JB.Debug_Info("Start","maps.google.com/maps/api/js?libraries=geometry&callback=JB.gmcb",false);
} // gmcb

JB.GPX2GM.start = function() {
	JB.Debug_Info("","GPXViewer "+JB.GPX2GM.ver+" vom "+JB.GPX2GM.dat,false);
	if(!JB.debuginfo && typeof(console) != "undefined" && typeof(console.info) == "function" )
		console.info("GPXViewer "+JB.GPX2GM.ver+" vom "+JB.GPX2GM.dat);
	JB.LoadCSS(JB.GPX2GM.Path+"GPX2GM.css");
	JB.LoadScript(JB.GPX2GM.Path+"GPX2GM_Defs.js", function() { 
		JB.GPX2GM.setparameters();
		JB.GPX2GM.parameters.mapapi = JB.GPX2GM.check_API();
		if(!JB.GPX2GM.GM_usage_ok()) return;
		JB.Scripte.maputils = 1;
		JB.Scripte.maplib = 1;
		if(JB.GPX2GM.parameters.mapapi=="gm") {
			var gmurl = "https://maps.google.com/maps/api/js?libraries=geometry&callback=JB.gmcb";
			if(JB.GPX2GM.GM_Api_key && (location.protocol=="https:" || location.protocol=="http:") ) gmurl += "&key="+JB.GPX2GM.GM_Api_key;
			if(document.documentElement.hasAttribute("lang") && document.documentElement.getAttribute("lang")!="de") gmurl += "&language=en";
			JB.LoadScript(gmurl, function() {}); 
			JB.LoadScript(JB.GPX2GM.Path+"gmutils.js", function() { JB.Scripte.maputils = 2; } );
		}
		else {
			JB.LoadScript(JB.GPX2GM.Path+"leaflet/leaflet.js", function() { JB.Scripte.maplib = 2; });
			JB.LoadCSS(JB.GPX2GM.Path+"leaflet/leaflet.css");
			JB.LoadScript(JB.GPX2GM.Path+"osmutils.js", function() { JB.Scripte.maputils = 2; } );
		}
		JB.Scripte.GPX2GM_Defs = 2;
		JB.icons = new JB.Icons(JB.GPX2GM.Path);
		JB.Debug_Info("Start","Icons vorbereitet",false);
		var Map_Nr=0;
		var divs = document.querySelectorAll("div[class*='gpxview:'],figure[class*='gpxview:']");
		var typ = undefined;
		var maps=[];
		var trenner_re = JB.GPX2GM.parameters.dateitrenner.replace(/[$*+?|.@^]/g,"\\$&");
		var re = new RegExp('\\s*'+trenner_re+'\\s*', 'g');
		for(var i=0;i<divs.length;i++) {
			var div = divs[i];
			var Klasse = div.className;
			Klasse = Klasse.replace(re,JB.GPX2GM.parameters.dateitrenner);
			Klasse = Klasse.replace(/\s*:\s*/g,":");
			Klasse = Klasse.replace(/\s*;\s*/g,";");
			if(div.id) var Id = div.id;
			else {
				var Id = "map"+(Map_Nr++);
				div.id = Id;
			}
			var CN = Klasse.search(/(^|\s)gpxview/i);
			var GPX = decodeURI(Klasse.substring(CN+1));
			if(GPX.search(";")>=0 && JB.GPX2GM.parameters.dateitrenner != ";") {
				GPX = GPX.replace("xview:","xview;");
				GPX = GPX.split(";");    
			}
			else {
				GPX = GPX.split(":");
			}
			if(GPX.length>=3) {
				typ = GPX[2];
			}
			maps["Karte_"+Id] = div.makeMap = new JB.makeMap(Id);
			maps["Karte_"+Id].ShowGPX(GPX[1].split(JB.GPX2GM.parameters.dateitrenner),typ);
		}
		var buttons = document.querySelectorAll("button[class*='gpxview:']");
		for(var i=0;i<buttons.length;i++) {
			var button = buttons[i];
			var Klasse = button.className;
			Klasse = Klasse.replace(re,JB.GPX2GM.parameters.dateitrenner);
			Klasse = Klasse.replace(/\s*:\s*/g,":");
			Klasse = Klasse.replace(/\s*;\s*/g,";");
			var CN = Klasse.search(/gpxview:/i); 
			var cmd = decodeURI(Klasse.substring(CN));
			if(cmd.search(";")>=0 && JB.GPX2GM.parameters.dateitrenner != ";") cmd = cmd.split(";") ;
			else cmd = cmd.split(":") ;
			if(cmd.length>2) {
				var Id = cmd[1];
				switch(cmd[2]) {
					case "skaliere":
						( function() {
							var mapid = "Karte_"+Id;
							if(cmd.length == 3) 
								button.onclick = function(){maps[mapid].Rescale()};
							else if(cmd.length == 4) {
								var pars = cmd[3].split(",");
								button.onclick = function(){maps[mapid].Rescale(pars[0],pars[1],pars[2])};
							}
						} )();
						break;
					case "lade":
						if(cmd.length>3) {
							if(cmd.length>4) typ = cmd[4];
							else typ = "";
							( function() {
								var fn = cmd[3].split(JB.GPX2GM.parameters.dateitrenner);
								var mapid = "Karte_"+Id;
								var tp = typ;
								button.onclick = function(){maps[mapid].ShowGPX(fn,tp)};
							} )();
						}
						break;
					default:
						break;
				}
			}
		}
		var selects = document.querySelectorAll("select[class^='gpxview']");
		for(var i=0;i<selects.length;i++) {
			var select = selects[i];
			select.onchange = function() {
				var cmd = decodeURI(this.options[this.options.selectedIndex].value).split(":");
				if(cmd.length<2) return;
				if(cmd.length<3) cmd[2] = "";
				maps["Karte_"+cmd[0]].ShowGPX(cmd[1].split(JB.GPX2GM.parameters.dateitrenner),cmd[2]);
			}
		}
	}); // JB.LoadScript("GPX2GM_Defs.js")
} // JB.GPX2GM.start

JB.GPX2GM.GM_usage_ok = function() {
	if(typeof(Bestaetigung)=="undefined" || Bestaetigung) {
		if(window.location.protocol=="file:") return true;
		var doc_lang = JB.GPX2GM.parameters.doclang.toLowerCase();
		if(doc_lang == "auto" && document.documentElement.hasAttribute("lang")) doc_lang = document.documentElement.getAttribute("lang");
		if(doc_lang in JB.GPX2GM.strings) JB.GPX2GM.parameters.doclang = doc_lang;
		else                              JB.GPX2GM.parameters.doclang = doc_lang = "de";
		if(localStorage) {
			var ls = localStorage.getItem("GM_OK");
			if(ls && ls.length) {
				return true;
			}
			else {
				var frage   = (JB.GPX2GM.parameters.mapapi=="gm")? JB.GPX2GM.strings[doc_lang].frage_datenschutz_gm : 
				                                    JB.GPX2GM.strings[doc_lang].frage_datenschutz_osm ;
				var antwort = (JB.GPX2GM.parameters.mapapi=="gm")? JB.GPX2GM.strings[doc_lang].antwort_datenschutz_gm : 
				                                    JB.GPX2GM.strings[doc_lang].antwort_datenschutz_osm ;
				if(confirm(frage)) {
					localStorage.setItem("GM_OK","Zustimmung, Google Maps zu nutzen, wurde erteilt.");
					return true;
				}
				else {
					var mapdivs = document.querySelectorAll("div[class*='gpxview:'],figure[class*='gpxview:']");
					for(var i=0;i<mapdivs.length;i++)
						mapdivs[i].innerHTML = "<p style='padding:2em;text-align:center;'>"+antwort+"</p>";
					return false;
				}
			}
		}
	}
	else {
		return true;
	}
} // JB.GPX2GM.GM_usage_ok

JB.GPX2GM.check_API = function() {
	if(JB.GPX2GM.parameters && JB.GPX2GM.parameters.mapapi) var mapapi = JB.GPX2GM.parameters.mapapi;
	else if(typeof(Mapapi)!="undefined") var mapapi = Mapapi;
	else var mapapi = "osm";
	if(window.location.search) {
		var pars = decodeURI(window.location.search.substring(1));
		var parsarr = pars.split(";");
		for(var i=0;i<parsarr.length;i++) {
			var parset = parsarr[i].split("=");
			if(parset[0] == "map_api" || parset[0] == "mapapi") {
				switch(parset[1]) { 
					case "osm":
						mapapi = "osm"; 
						break;
					case "gm":
						mapapi = "gm"; 
						break;
				}
				break;
			}
		}
	}
	return mapapi;
}

if(JB.GPX2GM.autoload) {
	if(window.addEventListener) {
		if(document.readyState == "loading")
			window.addEventListener("DOMContentLoaded",JB.GPX2GM.start,false);
		else
			JB.GPX2GM.start();
	}
	else {
		window.onload = function() { 
			document.querySelectorAll("div[class*='gpxview:'],figure[class*='gpxview:']")[0].innerHTML = "<p style='font-weight:bold;padding:2em;text-align:center;background-color:#fb5'>Leider wird Ihr Browser vom GPX-Viewer nicht mehr unterstützt.</p>";
			console.error("Leider wird Ihr Browser vom GPX-Viewer nicht mehr unterstützt.");
		}
	}
}
