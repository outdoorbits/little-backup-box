// Lizenz CC BY-NC-SA 4.0
// Jürgen Berkemeier
// www.j-berkemeier.de
// Platz für weitere Definitionen
// 25. 3. 2020

"use strict";

window.JB = window.JB || {};
window.JB.GPX2GM = window.JB.GPX2GM || {};

// Google Maps API Key
// JB.GPX2GM.GM_Api_key = "";
// Key für OSM Cycle
// JB.GPX2GM.OSM_Cycle_Api_Key = "";
// Key für OSM Landscape
// JB.GPX2GM.OSM_Landscape_Api_Key = "";

//var Mapapi = "gm";

// Definition der Icons, bei eigenen Icons nur Kleinbuchstaben verwenden.
JB.Icons = function(baseurl) {
	this.DefShadow	= { shadow: { anchor: {x:10,y:35}, url: baseurl+"Icons/shadow50.png" } };
	this.Bild				= { icon:   { anchor: {x: 6,y:31}, url: baseurl+"Icons/scenic.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	//this.MoveMarker	= { icon:   { anchor: {x: 6,y: 6}, url: baseurl+"Icons/marker.gif" } };
	this.MoveMarker	= { icon:   { anchor: {x: 6,y: 6}, url: baseurl+"Icons/marker.svg", 
											scaledSize: { width: 11, height: 11, widthUnit: "px", heightUnit: "px" },
											size: { width: 11, height: 11, widthUnit: "px", heightUnit: "px" } } };
	//this.Cluster		= { icon:   { anchor: {x:16,y:16}, url: baseurl+"Icons/cluster.png" } };
	this.Cluster 		= { icon:   { anchor: {x:16,y:16}, url: baseurl+"Icons/cluster.svg", 
											scaledSize: { width: 31, height: 31, widthUnit: "px", heightUnit: "px" },
											size: { width: 31, height: 31, widthUnit: "px", heightUnit: "px" } } };
	this.Streckenmarker 		= { icon:   { anchor: {x:11,y:11}, url: baseurl+"Icons/streckenmarker.svg", 
											scaledSize: { width: 21, height: 21, widthUnit: "px", heightUnit: "px" },
											size: { width: 21, height: 21, widthUnit: "px", heightUnit: "px" } } };
	this.Kreis			= { icon:   { anchor: {x:38,y:38}, url: baseurl+"Icons/kreis.png" } };
	this.marker_bw	= { icon:   { anchor: {x:12,y:37}, url: baseurl+"Icons/marker-icon_bw.png" } };
	this.CL   			= { icon:   { anchor: {x:26,y:26}, url: baseurl+"Icons/current_location.svg", 
											scaledSize: { width: 51, height: 51, widthUnit: "px", heightUnit: "px" },
											size: { width: 51, height: 51, widthUnit: "px", heightUnit: "px" } } };
	this.lodging		= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/hotel2.png" },
	//this.lodging		= { icon:   { anchor: {x:15,y:31}, url: baseurl+"Icons/hotel.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.hotel = this.lodging;
	this.museum			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/museum.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.residence	= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/villa.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.library		= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/library.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.park				= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/park.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.castle			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/castle.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.airport		= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/airport.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.church			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/church2.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.bridge			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/bridge.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.bar				= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/bar.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.restaurant	= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/restaurant.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.start			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/start.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.finish			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/finish.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.cycling		= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/cycling.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.hiking			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/hiking.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.flag				= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/flag.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.harbor			= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/harbor.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.anchor			= this.harbor;
	this.campground	= { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/tent.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.summit     = { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/peak.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.railway    = { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/train.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this["shopping center"] = { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/shoppingmall.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this["ground transportation"] = { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/subway.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this["scenic area"] = { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/photo.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this["boat ramp"]   = { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/boat.png" },
											shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	this.circle_red 		= { icon:   { anchor: {x:8,y:6}, url: baseurl+"Icons/circle_red.svg", 
											scaledSize: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" },
											size: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" } } };
	this.circle_green		= { icon:   { anchor: {x:8,y:8}, url: baseurl+"Icons/circle_green.svg", 
											scaledSize: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" },
											size: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" } } };
	this.square_red 		= { icon:   { anchor: {x:8,y:8}, url: baseurl+"Icons/square_red.svg", 
											scaledSize: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" },
											size: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" } } };
	this.square_green		= { icon:   { anchor: {x:8,y:8}, url: baseurl+"Icons/square_green.svg", 
											scaledSize: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" },
											size: { width: 15, height: 15, widthUnit: "px", heightUnit: "px" } } };

	//this.myicon       = { icon:   { anchor: {x:15,y:36}, url: baseurl+"Icons/myicon.png" },
	//                    shadow: { anchor: {x:10,y:31}, url: baseurl+"Icons/shadow.png" } };
	// Most Icons from https://mapicons.mapsmarker.com/
} ;   

JB.GPX2GM.setparameters = function() {  
	JB.GPX2GM.parameters = {};
	JB.GPX2GM.parameters.mapapi = (typeof(Mapapi)!="undefined") ? Mapapi : "osm";   // gm oder osm
	JB.GPX2GM.parameters.doclang = (typeof(Doclang)!="undefined") ? Doclang : "auto"; // de, fr oder en
	JB.GPX2GM.parameters.unit = (typeof(Unit)!="undefined") ? Unit : "si"; // enus oder en oder us oder air oder water = airwater
	JB.GPX2GM.parameters.showmaptypecontroll = (typeof(Showmaptypecontroll)!="undefined") ? Showmaptypecontroll : true;
	JB.GPX2GM.parameters.scrollwheelzoom = (typeof(Scrollwheelzoom)!="undefined") ? Scrollwheelzoom : true;
	JB.GPX2GM.parameters.fullscreenbutton = (typeof(Fullscreenbutton)!="undefined") ? Fullscreenbutton : false;
	JB.GPX2GM.parameters.currentlocationbutton = (typeof(Currentlocationbutton)!="undefined") ? Currentlocationbutton : false;
	JB.GPX2GM.parameters.trafficbutton = (typeof(Trafficbutton)!="undefined") ? Trafficbutton : false;
	JB.GPX2GM.parameters.trafficonload = (typeof(Trafficonload)!="undefined") ? Trafficonload : true;
	JB.GPX2GM.parameters.legende = (typeof(Legende)!="undefined") ? Legende : true;
	JB.GPX2GM.parameters.legende_fnm = (typeof(Legende_fnm)!="undefined") ? Legende_fnm  : true;
	JB.GPX2GM.parameters.legende_fnm_lm = (typeof(Legende_fnm_lm)!="undefined") ? Legende_fnm_lm  : false;
	JB.GPX2GM.parameters.legende_rr = (typeof(Legende_rr)!="undefined") ? Legende_rr  : true;
	JB.GPX2GM.parameters.legende_trk = (typeof(Legende_trk)!="undefined") ? Legende_trk : true;
	JB.GPX2GM.parameters.legende_rte = (typeof(Legende_rte)!="undefined") ? Legende_rte : true;
	JB.GPX2GM.parameters.legende_wpt = (typeof(Legende_wpt)!="undefined") ? Legende_wpt : true;
	JB.GPX2GM.parameters.gpxtracks = (typeof(Gpxtracks)!="undefined") ? Gpxtracks : true; 
	JB.GPX2GM.parameters.gpxrouten = (typeof(Gpxrouten)!="undefined") ? Gpxrouten : true; 
	JB.GPX2GM.parameters.gpxwegpunkte = (typeof(Gpxwegpunkte)!="undefined") ? Gpxwegpunkte : true;
	JB.GPX2GM.parameters.tracks_verbinden = (typeof(Tracks_verbinden)!="undefined") ? Tracks_verbinden : false;    
	JB.GPX2GM.parameters.tracks_dateiuebergreifend_verbinden = (typeof(Tracks_dateiuebergreifend_verbinden)!="undefined") ? Tracks_dateiuebergreifend_verbinden : false;
	JB.GPX2GM.parameters.tracksort = (typeof(Tracksort)!="undefined") ? Tracksort : true;
	JB.GPX2GM.parameters.dateitrenner = (typeof(Dateitrenner)!="undefined") ? Dateitrenner : ",";
	JB.GPX2GM.parameters.readspeed = (typeof(Readspeed)!="undefined") ? Readspeed : true;
	JB.GPX2GM.parameters.speedfaktor = (typeof(Speedfaktor)!="undefined") ? Speedfaktor : 3.6; // 3.6 bei m/s, 1,609344 bei mph, 1 bei km/h
	JB.GPX2GM.parameters.hfaktor = (typeof(Hfaktor)!="undefined") ? Hfaktor : 1; // Höhe
	JB.GPX2GM.parameters.sfaktor = (typeof(Sfaktor)!="undefined") ? Sfaktor : 1; // Steigung
	JB.GPX2GM.parameters.vfaktor = (typeof(Vfaktor)!="undefined") ? Vfaktor : 1; // Geschwindigkeit
	JB.GPX2GM.parameters.wfaktor = (typeof(Wfaktor)!="undefined") ? Wfaktor : 1; // Weg
	JB.GPX2GM.parameters.tfaktor = (typeof(Tfaktor)!="undefined") ? Tfaktor : 1; // Temperatur
	JB.GPX2GM.parameters.toffset = (typeof(Toffset)!="undefined") ? Toffset : 0; // Temperatur
	JB.GPX2GM.parameters.unwraplon = (typeof(Unwraplon)!="undefined") ? Unwraplon : true;
	JB.GPX2GM.parameters.trackover = (typeof(Trackover)!="undefined") ? Trackover : true;
	JB.GPX2GM.parameters.trackclick = (typeof(Trackclick)!="undefined") ? Trackclick : true;
	JB.GPX2GM.parameters.trackmarker = (typeof(Trackmarker)!="undefined") ? Trackmarker : "";
	JB.GPX2GM.parameters.routemarker = (typeof(Routemarker)!="undefined") ? Routemarker : "";
	JB.GPX2GM.parameters.shwpname = (typeof(Shwpname)!="undefined") ? Shwpname : true;
	JB.GPX2GM.parameters.shwpcmt = (typeof(Shwpcmt)!="undefined") ? Shwpcmt : true;
	JB.GPX2GM.parameters.shwpdesc = (typeof(Shwpdesc)!="undefined") ? Shwpdesc : false;
	JB.GPX2GM.parameters.shwptime = (typeof(Shwptime)!="undefined") ? Shwptime : false;
	JB.GPX2GM.parameters.shwpshadow = (typeof(Shwpshadow)!="undefined") ? Shwpshadow : true;
	JB.GPX2GM.parameters.wpcluster = (typeof(Wpcluster)!="undefined") ? Wpcluster : false;
	JB.GPX2GM.parameters.bildpfad = (typeof(Bildpfad)!="undefined") ? Bildpfad : "";
	JB.GPX2GM.parameters.gpxpfad = (typeof(Gpxpfad)!="undefined") ? Gpxpfad : ""; 
	JB.GPX2GM.parameters.bildwegpunkticon = (typeof(Bildwegpunkticon)!="undefined") ? Bildwegpunkticon : "Bild"; // Bei "" Icon aus sym-Tag
	JB.GPX2GM.parameters.shtrcmt = (typeof(Shtrcmt)!="undefined") ? Shtrcmt : false;
	JB.GPX2GM.parameters.shtrdesc = (typeof(Shtrdesc)!="undefined") ? Shtrdesc : false;
	JB.GPX2GM.parameters.shtrx = (typeof(Shtrx)!="undefined") ? Shtrx : true;
	JB.GPX2GM.parameters.shtrt = (typeof(Shtrt)!="undefined") ? Shtrt : true;
	JB.GPX2GM.parameters.shtrtwob = (typeof(Shtrtwob)!="undefined") ? Shtrtwob : false;
	JB.GPX2GM.parameters.shtrtabs = (typeof(Shtrtabs)!="undefined") ? Shtrtabs : false;
	JB.GPX2GM.parameters.shtrtabs_k = JB.GPX2GM.parameters.shtrtabs_p = false;
	if(JB.GPX2GM.parameters.shtrtabs==true) JB.GPX2GM.parameters.shtrtabs_k = JB.GPX2GM.parameters.shtrtabs_p = true;
	if(typeof(JB.GPX2GM.parameters.shtrtabs)=="string" && JB.GPX2GM.parameters.shtrtabs.indexOf("k")>-1 ) JB.GPX2GM.parameters.shtrtabs_k = true;;
	if(typeof(JB.GPX2GM.parameters.shtrtabs)=="string" && JB.GPX2GM.parameters.shtrtabs.indexOf("p")>-1 ) JB.GPX2GM.parameters.shtrtabs_p = true;;
	JB.GPX2GM.parameters.shtrtges = (typeof(Shtrtges)!="undefined") ? Shtrtges : false;
	JB.GPX2GM.parameters.shtrtgeswob = (typeof(Shtrtgeswob)!="undefined") ? Shtrtgeswob : false;
	JB.GPX2GM.parameters.shtrv = (typeof(Shtrv)!="undefined") ? Shtrv : true;
	JB.GPX2GM.parameters.shtrpace = (typeof(Shtrpace)!="undefined") ? Shtrpace : false;
	JB.GPX2GM.parameters.shtrh = (typeof(Shtrh)!="undefined") ? Shtrh : true;
	JB.GPX2GM.parameters.shtrrr = (typeof(Shtrrr)!="undefined") ? Shtrrr : true;
	JB.GPX2GM.parameters.shtrs = (typeof(Shtrs)!="undefined") ? Shtrs : true;
	JB.GPX2GM.parameters.shtrhr = (typeof(Shtrhr)!="undefined") ? Shtrhr : true;
	JB.GPX2GM.parameters.shtrcad = (typeof(Shtrcad)!="undefined") ? Shtrcad : true;
	JB.GPX2GM.parameters.shtratemp = (typeof(Shtratemp)!="undefined") ? Shtratemp : true;
	JB.GPX2GM.parameters.shtrvmitt = (typeof(Shtrvmitt)!="undefined") ? Shtrvmitt : false;
	JB.GPX2GM.parameters.shtrvmittwob = (typeof(Shtrvmittwob)!="undefined") ? Shtrvmittwob : false;
	JB.GPX2GM.parameters.shtrvmittpace = (typeof(Shtrvmittpace)!="undefined") ? Shtrvmittpace : false;
	JB.GPX2GM.parameters.shtrvmittpacewob = (typeof(Shtrvmittpacewob)!="undefined") ? Shtrvmittpacewob : false;
	JB.GPX2GM.parameters.movevmin = (typeof(Movevmin)!="undefined") ? Movevmin : 1;
	JB.GPX2GM.parameters.arrowtrack = (typeof(Arrowtrack)!="undefined") ? Arrowtrack : false;
	JB.GPX2GM.parameters.arrowtrackcol = (typeof(Arrowtrackcol)!="undefined") ? Arrowtrackcol : "";
	JB.GPX2GM.parameters.shrtcmt = (typeof(Shrtcmt)!="undefined") ? Shrtcmt : false;
	JB.GPX2GM.parameters.shrtdesc = (typeof(Shrtdesc)!="undefined") ? Shrtdesc : false;
	JB.GPX2GM.parameters.shtrstart = (typeof(Shtrstart)!="undefined") ? Shtrstart : false;
	JB.GPX2GM.parameters.shtrziel = (typeof(Shtrziel)!="undefined") ? Shtrziel : false;
	JB.GPX2GM.parameters.shrtstart = (typeof(Shrtstart)!="undefined") ? Shrtstart : false;
	JB.GPX2GM.parameters.shrtziel = (typeof(Shrtziel)!="undefined") ? Shrtziel : false;
	JB.GPX2GM.parameters.arrowroute = (typeof(Arrowroute)!="undefined") ? Arrowroute : false
	JB.GPX2GM.parameters.arrowroutecol = (typeof(Arrowroutecol)!="undefined") ? Arrowroutecol : "";
	JB.GPX2GM.parameters.arrowsymbol = (typeof(Arrowsymbol)!="undefined") ? Arrowsymbol : "➤"; 
	JB.GPX2GM.parameters.groesseminibild	= (typeof(Groesseminibild)!="undefined") ? Groesseminibild : 60; // in Pixel, max. 149
	JB.GPX2GM.parameters.displaycolor = (typeof(Displaycolor)!="undefined") ? Displaycolor : false;
	JB.GPX2GM.parameters.laengen3d = (typeof(Laengen3d)!="undefined") ? Laengen3d : false;
	JB.GPX2GM.parameters.usegpxbounds = (typeof(Usegpxbounds)!="undefined") ? Usegpxbounds : false;
	JB.GPX2GM.parameters.hglattlaen = (typeof(Hglattlaen)!="undefined") ? Hglattlaen : 500; // in Meter
	JB.GPX2GM.parameters.vglattlaen = (typeof(Vglattlaen)!="undefined") ? Vglattlaen : 100; // in Meter
	JB.GPX2GM.parameters.vglatt = (typeof(Vglatt)!="undefined") ? Vglatt : false;
	JB.GPX2GM.parameters.hglatt = (typeof(Hglatt)!="undefined") ? Hglatt : false;
	JB.GPX2GM.parameters.tdiff = (typeof(Tdiff)!="undefined") ? Tdiff : 0; // in Stunden
	JB.GPX2GM.parameters.tkorr = (typeof(Tkorr)!="undefined") ? Tkorr : true;
	JB.GPX2GM.parameters.maxzoomemove = (typeof(Maxzoomemove)!="undefined") ? Maxzoomemove : 30; // 1 ... , 30: aus
	JB.GPX2GM.parameters.plotframecol = (typeof(Plotframecol)!="undefined") ? Plotframecol : "black";
	JB.GPX2GM.parameters.plotgridcol = (typeof(Plotgridcol)!="undefined") ? Plotgridcol : "gray";
	JB.GPX2GM.parameters.plotlabelcol = (typeof(Plotlabelcol)!="undefined") ? Plotlabelcol : "black";
	JB.GPX2GM.parameters.plotmarkercol = (typeof(Plotmarkercol)!="undefined") ? Plotmarkercol : "black";
	JB.GPX2GM.parameters.profilfillopac = (typeof(Profilfillopac)!="undefined") ? Profilfillopac : 0; //   0 ... 1, 0:aus
	JB.GPX2GM.parameters.trcolmod = (typeof(Trcolmod)!="undefined") ? Trcolmod : ""; // h s v hr cad
	JB.GPX2GM.parameters.tcols = (typeof(Tcols)!="undefined") ? Tcols : ["#ff0000","#00ff00","#0000ff","#eeee00","#ff00ff","#00ffff","#000000"]; // Trackfarben in #rrggbb für rot grün blau
	JB.GPX2GM.parameters.rcols = (typeof(Rcols)!="undefined") ? Rcols : ["#800000","#008000","#000080","#808000","#800080","#008080","#808080"]; // Routenfarben
	JB.GPX2GM.parameters.ocol = (typeof(Ocol)!="undefined") ? Ocol : "#000000";   // Track- und Routenfarbe bei Mouseover
	JB.GPX2GM.parameters.owidth = (typeof(Owidth)!="undefined") ? Owidth : 3.0;  // Linienstärke Track und Route bei Mouseover
	JB.GPX2GM.parameters.twidth = (typeof(Twidth)!="undefined") ? Twidth : 2.0;  // Linienstärke Track
	JB.GPX2GM.parameters.rwidth = (typeof(Rwidth)!="undefined") ? Rwidth : 2.0;  // Linienstärke Route
	JB.GPX2GM.parameters.topac = (typeof(Topac)!="undefined") ? Topac : 0.8;   // Transparenz Trackfarbe
	JB.GPX2GM.parameters.ropac = (typeof(Ropac)!="undefined") ? Ropac : 0.8;   // Transparenz Routenfarbe
	JB.GPX2GM.parameters.linktarget = (typeof(Linktarget)!="undefined") ? Linktarget : "";
	JB.GPX2GM.parameters.popup_Pars = (typeof(Popup_Pars)!="undefined") ? Popup_Pars : "width=900,height=790,screenX=970,screenY=0,status=yes,scrollbars=yes";
	
	if(JB.GPX2GM.parameters.tracks_dateiuebergreifend_verbinden) JB.GPX2GM.parameters.tracks_verbinden = true;
	if(typeof(JB.GPX2GM.parameters.tcols)=="string") JB.GPX2GM.parameters.tcols = JB.GPX2GM.parameters.tcols.split(",");
	if(typeof(JB.GPX2GM.parameters.rcols)=="string") JB.GPX2GM.parameters.rcols = JB.GPX2GM.parameters.rcols.split(",");

	if(JB.debuginfo) {
		var t = "";
		for(var o in JB.GPX2GM.parameters) t += "<br>&nbsp;&nbsp;" + o + ": " + JB.GPX2GM.parameters[o];
		JB.Debug_Info("Start","Globale Steuervariablen: "+t+"<br>",false);
	}
}

JB.GPX2GM.units = {};
JB.GPX2GM.units.si =  {
	way: "km",
	speed: "km/h",
	alt: "m",
	pace: "min/km",
	temp: "°C"
};
JB.GPX2GM.units.enus = JB.GPX2GM.units.us = JB.GPX2GM.units.en =  {
	way: "miles",
	speed: "mph",
	alt: "ft",
	pace: "min/mile",
	temp: "°C"
};
JB.GPX2GM.units.us.temp = "°F";
JB.GPX2GM.units.airwater = JB.GPX2GM.units.water =  {
	way: "sm",
	speed: "kn",
	alt: "ft",
	pace: "min/sm",
	temp: "°C"
};
JB.GPX2GM.units.air =  {
	way: "NM",
	speed: "kn",
	alt: "ft",
	pace: "min/NM",
	temp: "°C"
};

JB.GPX2GM.strings = {};
JB.GPX2GM.strings.de = {
	lenght: "L\u00e4nge",
	way: "Strecke",
	duration: "Dauer",
	tstart: "Startzeit",
	time: "Zeit",
	time_unit: "Stunden",
	altdiff: "H\u00F6hendifferenz",
	alt: "H\u00F6he",
	in: " in ",
	grade: "Stg.",
	grade_unit: "%",
	avspeed: "V<sub>m</sub>",
	speed2: "Geschw.",
	speed: "V",
	pace: "Pace",
	hr2: "Puls",
	hr: "HF",
	hr_unit: "1/min",
	cad: "Cadenz",  
	cad_unit: "UpM",
	temp: "Temperatur",
	wpt: "Wegpunkt",
	wpts: "Wegpunkte",
	pwpt: "Bildwegpunkt",
	trk: "Track",
	trks: "Tracks",
	rte: "Route",
	rtes: "Routen",
	inmo: "in Bewegung",
	// wait: "Bitte warten.<br />Daten werden geladen.",
	wait: "",  // Wartebild nehmen
	clkz: "Zum Zoomen klicken",
	zb: "Zurück zoomen",
	frage_datenschutz_gm: "Diese Seite verwendet Karten und ein Api von Google sowie möglicherweise auch OSM-Karten. Dadurch werden Besucherdaten an den jeweiligen Dienstanbieter übertragen. Mehr dazu im Impressum. Ist das OK?",
	antwort_datenschutz_gm: "Die Zustimmung zur Nutzung des Google Maps API wurde verweigert. Beim erneuten Laden der Seite können Sie ihre Meinung ändern.",
	frage_datenschutz_osm: "Diese Seite verwendet OSM-Karten. Dadurch werden Besucherdaten an den jeweiligen Dienstanbieter übertragen. Mehr dazu im Impressum. Ist das OK?",
	antwort_datenschutz_osm: "Die Zustimmung zur Nutzung der OSM-Karten wurde verweigert. Beim erneuten Laden der Seite können Sie ihre Meinung ändern.",
	fullScreen: "Full Screen",
	normalSize: "Normale Gr\u00F6\u00dfe",
	showCurrentLocation: "Aktuelle Position anzeigen",
	hideCurrentLocation: "Aktuelle Position verbergen",
	showTrafficLayer: "Verkehr anzeigen",
	hideTrafficLayer: "Verkehr verbergen",
	noMap: "Keine Karte",
	file: "Datei",
	showhide: "ein-/ausblenden",
	zoom: "auf Element zoomen",
	all: "Alle",
	showlist: "Liste anzeigen, bewegen in der Liste mit den Pfeiltasten"
}
// Französische Texte von Jean-Jacques und Pierre-Michel Sarton
JB.GPX2GM.strings.fr = {
	lenght: "Distance au point donné",
	way: "Distance totale",
	duration: "Durée totale",
	tstart: "Date du trajet",
	time: "Durée à ce point",
	time_unit: "heures",
	altdiff: "Dénivelés",
	alt: "Altitude", //"Altitude",
	//alt_unit: "m",
	in: " en ",
	grade: "Pente",
	grade_unit: "%",
	avspeed: "Vitesse moyenne",
	speed2: "Vitesse instantannée",
	speed: "V",
	pace: "Pace",
	hr2: "Pouls",
	hr: "Pouls",
	hr_unit: "1/min",
	cad: "Cadence", 
	cad_unit: "t/min",
	temp: "Température",
	wpt: "Waypoint",
	wpts: "Waypoints",
	pwpt: "PWaypoint de l'image",
	trk: "Tracé",
	trks: "Tracés",
	rte: "Itinéraire",
	rtes: "Itinéraires",
	inmo: "En mouvement",
	// wait: "Please wait.<br />Loading data.",
	wait: "",  // Wartebild nehmen
	clkz: "Cliquez pour agrandir",
	zb: "Retour du Zoom",
	frage_datenschutz_gm: "Cette page utilise des cartes et une API de Google et éventuellement aussi de cartes OSM. Cela transfère les données des visiteurs au fournisseur des services respectifs. Pour savoir plus voire les mentions légales. Est-ce que tout va bien ?",
	antwort_datenschutz_gm: "L'autorisation d'utiliser l'API Google Maps a été refusée. Vous pouvez changer d'avis lorsque vous rechargez la page.",
	frage_datenschutz_osm: "Cette page utilise des cartes OSM (OpenStreetMap). Les données des visiteurs sont transférées au fournisseur des services respectifs. En poursuivant votre navigation sur ce site, vous acceptez la transmission de vos informations",
	antwort_datenschutz_osm: "L'autorisation d'utiliser les cartes OSM a été refusée. Vous pouvez changer d'avis lorsque vous rechargez la page.",
	fullScreen: "Plein écran",
	normalSize: "Taille normale",
	showCurrentLocation: "Afficher la position actuelle",
	hideCurrentLocation: "cacher la position actuelle",
	showTrafficLayer: "Afficher le traffic",
	hideTrafficLayer: "Cacher le traffic",
	noMap: "Pas de carte",
	file: "File",
	showhide: "afficher/masquer",
	zoom: "zoom sur élément",
	all: "Tous",
	showlist: "afficher la liste, se déplacer dans la liste avec les touches fléchées"
}
JB.GPX2GM.strings.en = {
	lenght: "Length",
	way: "Way",
	duration: "Duration",
	tstart: "Start time",
	time: "Time",
	time_unit: "hours",
	altdiff: "Elevation difference",
	alt: "Elevation", //"Altitude",
	//alt_unit: "m",
	in: " in ",
	grade: "Grade",
	grade_unit: "%",
	avspeed: "V<sub>m</sub>",
	speed2: "Speed",
	speed: "V",
	pace: "Pace",
	hr2: "Heart rate",
	hr: "HR",
	hr_unit: "1/min",
	cad: "Cadence", 
	cad_unit: "rpm",
	temp: "Temperature",
	wpt: "Waypoint",
	wpts: "Waypoints",
	pwpt: "Picture Waypoint",
	trk: "Track",
	trks: "Tracks",
	rte: "Route",
	rtes: "Routes",
	inmo: "in motion",
	// wait: "Please wait.<br />Loading data.",
	wait: "",  // Wartebild nehmen
	clkz: "Click to zoom",
	zb: "Zoom back",
	frage_datenschutz_gm: "This page uses maps and an api from Google and possibly also OSM maps. This transfers visitor data to the respective service provider. Read more about this in the imprint. Is that all right?",
	antwort_datenschutz_gm: "Permission to use the Google Maps API has been denied. You can change your mind when you reload the page.",
	frage_datenschutz_osm: "This page uses OSM maps. This transfers visitor data to the respective service provider. Read more about this in the imprint. Is that all right?",
	antwort_datenschutz_osm: "Permission to use the OSM maps has been denied. You can change your mind when you reload the page.",
	fullScreen: "Full Screen",
	normalSize: "Normal Size",
	showCurrentLocation: "Show current location",
	hideCurrentLocation: "Hide current location",
	showTrafficLayer: "Show traffic",
	hideTrafficLayer: "Hide traffic",
	noMap: "No Map",
	file: "File",
	showhide: "show/hide",
	zoom: "zoom to element",
	all: "All",
	showlist: "show list, use the arrow keys to navigate in the list"
}
JB.GPX2GM.strings.es = {
	lenght: "Longitud",
	way: "Distancia",
	duration: "Duraci\u00F3n",
	tstart: "Hora de inicio",
	time: "Hora",
	time_unit: "horas",
	altdiff: "Diferencia de altitud",
	alt: "Altitud",
	in: " en ",
	grade: "Incl.",
	grade_unit: "%",
	avspeed: "V<sub>m</sub>",
	speed2: "Veloc.",
	speed: "V",
	pace: "Ritmo",
	hr2: "Pulso",
	hr: "HR",
	hr_unit: "1/min",
	cad: "Cadencia",
	cad_unit: "rpm",
	temp: "Temperatura",
	wpt: "Waypoint",
	wpts: "Waypoints",
	pwpt: "Imagen del Waypoint",
	trk: "Track",
	trks: "Tracks",
	rte: "Ruta",
	rtes: "Rutas",
	inmo: "en movimiento",
	// wait: "Por favor, espera.<br />Cargando datos."
	wait: "", // Wartebild nehmen
	clkz: "Clic para ampliar",
	zb: "Retroceder",
	frage_datenschutz_gm: "Esta p\u00E1gina utiliza mapas y un API de Google y posiblemente tambi\u00E9n mapas de OSM. Esto transfiere los datos de los visitantes al respectivo proveedor de servicios. Lea m\u00E1s sobre esto en el pie de imprenta. \u00BFEest\u00E1 bien as\u00ED?",
	antwort_datenschutz_gm: "El permiso para usar la API de Google Maps ha sido denegado. Puedes cambiar esta opci\u00F3n cuando recargues la p\u00E1gina",
	frage_datenschutz_osm: "Esta p\u00E1gina utiliza los mapas de OSM. Esto transfiere los datos de los visitantes al respectivo proveedor de servicios. Lee m\u00E1s sobre esto en el pie de imprenta. \u00BFEst\u00E1 bien as\u00ED?",
	antwort_datenschutz_osm: "Se ha denegado el permiso para utilizar los mapas de OSM. Puedes cambiar esta opci\u00F3n cuando recargues la p\u00E1gina",
	fullScreen: "Pantalla completa",
	normalSize: "Tamaño normal",
	showCurrentLocation: "Mostrar ubicaci\u00F3n actual",
	hideCurrentLocation: "Ocultar ubicaci\u00F3n actual",
	showTrafficLayer: "Mostrar tr\u00E1fico",
	hideTrafficLayer: "Ocultar tr\u00E1fico",
	noMap: "Ning\u00FAn Mapa",
	file: "Archivo",
	showhide: "mostrar/ocultar",
	zoom: "zoom a elemento",
	all: "Todo",
	showlist: "Mostrar lista, moverse en la lista con las teclas de flecha"
}

/* // Prototyp für Callbackfunktion
JB.GPX2GM.callback = function(pars) {
	JB.Debug_Info("callback",pars.id+" "+pars.type,false);
	switch(pars.type) {
		case "Map_div_v" :
			break;
		case "Map_div_n" :
			break;
		case "Map_v":
			break;
		case "Map_n":
			break;
		case "Wegpunkte_v":
			break;
		case "Wegpunkte_n":
			break;
		case "Routen_v":
			break;
		case "Routen_n":
			break;
		case "Tracks_v":
			alert(pars.gpxdaten.tracks.laenge);
			for(var i=0;i<pars.gpxdaten.tracks.track.length;i++)
				alert(pars.gpxdaten.tracks.track[i].laenge); 
			break;
		case "Tracks_n":
			break;
		case "Profile_v":
			break;
		case "Profile_n":
			break;
		case "click_Marker_Text":
			break;
		case "click_Marker_Bild":
			break;
		case "created_Marker_Bild":
			break;
		case "click_Route":
			break;
		case "click_Track":
			console.info(pars);
			break;
	}
	return true;
} // JB.GPX2GM.callback */

/* // Autoscale in den Profilen abschalten
JB.Scaling = {   // nur paarweise verwenden
	hmin:0,hmax:1000,  // Höhenplot
	smin:-30,smax:30,  // Steigungsplot
	vmin:0,vmax:100,   // Geschwindigkeitsplot
	hrmin:50,hrmax:200,   // Herzfrequenz
	cadmin:0,cadmax:150,   // Trittfrequenz
	atempmin:-30,atempmax:50,   // Temperatur
	hardscaling:false   // Skalierwerte bindend (true) oder Minwerte(false)
}; */
