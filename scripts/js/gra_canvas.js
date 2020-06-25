// gra_canvas
// Version vom 3. 10. 2016
// Jürgen Berkemeier
// www.j-berkemeier.de

"use strict";

var JB = window.JB || {};

// Das Grafikobjekt
JB.grafik = function(grafikelement) {
	this.method = "canvas";
	// Canvas in Größe des "grafikelement" anlegen
	if(typeof grafikelement == "string") grafikelement = document.getElementById(grafikelement);
	this.w = grafikelement.offsetWidth;
	this.h = grafikelement.offsetHeight;
	var cv = document.createElement("canvas");
	cv.width = this.w;
	cv.height = this.h;
	cv.style.position = "absolute";
	grafikelement.appendChild(cv);
	var context = cv.getContext("2d"); 
	context.lineWidth = 1;
	context.globalAlpha = 1.0;
	
	// Linienstärke setzen
	this.setwidth = function(width) {
		context.lineWidth = width;
	} // setwidth
	
	// Punkt bei x,y, in Farbe c
	this.punkt = function(x,y,c) {
		context.fillStyle = c;
		context.fillRect(x-(context.lineWidth-1)/2,this.h-y+(context.lineWidth-1)/2,context.lineWidth,context.lineWidth);
	} // punkt

	// Linie von (xs,ys) nach (xe,ye) in Farbe color zeichnen
	this.line = function(xs,ys,xe,ye,color) {
		context.strokeStyle = color;
		context.beginPath();
		context.moveTo(Math.round(xs),Math.round(this.h-ys));
		context.lineTo(Math.round(xe),Math.round(this.h-ye));
		context.stroke();
	} // line

	// Polylinie mit den Werten in points in Farbe color zeichnen
	this.polyline = function(points,color) { 
		context.strokeStyle = color;
		context.beginPath();
		context.moveTo(Math.round(points[0].x),this.h-Math.round(points[0].y));
		for(var i=1,l=points.length;i<l;i++) 
			context.lineTo(Math.round(points[i].x),this.h-Math.round(points[i].y));
		context.stroke();
	} // polyline

	// Polylinie mit den Werten in points zeichnen
	// Die von der Polylinie umschlossene Fläche wird in Farbe color mit Alphawert alpha eingefärbt
	this.polyfill = function(points,color,alpha) { 
		context.fillStyle = color;
		context.globalAlpha = alpha;
		context.beginPath();
		context.moveTo(Math.round(points[0].x),this.h-Math.round(points[0].y));
		for(var i=1,l=points.length;i<l;i++) 
			context.lineTo(Math.round(points[i].x),this.h-Math.round(points[i].y));
		context.fill();
		context.globalAlpha = 1.0;
	} // polyfill
	
	// Text an (x,y) ausgeben
	// size: Schriftgröße
	// text: Text
	// align: Bezug für (x,y), zwei Buchstaben, z.B. lu für links unten, s. case
	// diretion: Textrichtung: v für vertikal, sonst horizontal
	this.text = function(x,y,size,color,text,align,direction) {
		var align_h = "m";
		var align_v = "m";
		if(align && align.length) {
			align_h = align.substr(0,1);
			if(align.length>1) align_v = align.substr(1,1);
		}
		context.save();
		context.translate(x,this.h-y);
		if(direction && direction=="v") 
			context.rotate(1.5*Math.PI);
		switch(align_h) {
			case "l": context.textAlign = "start"; break;
			case "m": context.textAlign = "center"; break;
			case "r": context.textAlign = "end"; break;
			default:  context.textAlign = "center"; break;
		}
		switch(align_v) {
			case "o": context.textBaseline = "top" ; break;
			case "m": context.textBaseline = "middle" ; break;
			case "u": context.textBaseline = "bottom" ; break;
			default:  context.textBaseline = "middle" ; break;
		}
		context.font = size + " sans-serif";
		context.fillStyle = color;
		context.fillText(text,0,0);
		context.restore();
	} // text

	// Canvas löschen
	this.del = function() {
		context.clearRect(0, 0, this.w, this.h);
	} // del
	
	// Textbreite ermiteln
	this.getTextWidth = function(text,size) {
		context.font = size+" sans-serif";
		return context.measureText(text).width;
	} // getTextWidth

} // grafik