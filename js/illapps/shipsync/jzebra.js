function print() {
	var applet = document.jZebra;
	if (applet != null) {
		applet.append64(label);
		applet.print();
	}

	monitorPrinting();
}

function chr(i) {
	return String.fromCharCode(i);
}

function findPrinter() {
	var applet = document.jZebra;
	if (applet != null) {
		// Searches for locally installed printer with "zebra" in the name
		applet.findPrinter("Zebra");
	}

	// *Note:  monitorFinding() still works but is too complicated and
	// outdated.  Instead create a JavaScript  function called
	// "jzebraDoneFinding()" and handle your next steps there.
	monitorFinding();
}

function findPrinters() {
	var applet = document.jZebra;
	if (applet != null) {
		// Searches for locally installed printer with "zebra" in the name
		applet.findPrinter("\\{dummy printer name for listing\\}");
	}

	monitorFinding2();
}

// *Note:  monitorPrinting() still works but is too complicated and
// outdated.  Instead create a JavaScript  function called
// "jzebraDonePrinting()" and handle your next steps there.
function monitorPrinting() {
	var applet = document.jZebra;
	if (applet != null) {
		if (!applet.isDonePrinting()) {
			window.setTimeout('monitorPrinting()', 100);
		} else {
			var e = applet.getException();
			alert(e == null ? "Printed Successfully" : "Exception occured: " + e.getLocalizedMessage());
		}
	} else {
		alert("Applet not loaded!");
	}
}

function monitorFinding() {
	var applet = document.jZebra;
	if (applet != null) {
		if (!applet.isDoneFinding()) {
			window.setTimeout('monitorFinding()', 100);
		} else {
			var printer = applet.getPrinter();
			alert(printer == null ? "Printer not found" : "Printer \"" + printer + "\" found");
		}
	} else {
		alert("Applet not loaded!");
	}
}

function monitorFinding2() {
	var applet = document.jZebra;
	if (applet != null) {
		if (!applet.isDoneFinding()) {
			window.setTimeout('monitorFinding2()', 100);
		} else {
			var printersCSV = applet.getPrinters();
			var printers = printersCSV.split(",");
			for (p in printers) {
				alert(printers[p]);
			}

		}
	} else {
		alert("Applet not loaded!");
	}
}

// *Note:  monitorAppending() still works but is too complicated and
// outdated.  Instead create a JavaScript  function called
// "jzebraDoneAppending()" and handle your next steps there.
function monitorAppending() {
	var applet = document.jZebra;
	if (applet != null) {
		if (!applet.isDoneAppending()) {
			window.setTimeout('monitorAppending()', 100);
		} else {
			applet.print(); // Don't print until all of the data has been appended

			// *Note:  monitorPrinting() still works but is too complicated and
			// outdated.  Instead create a JavaScript  function called
			// "jzebraDonePrinting()" and handle your next steps there.
			monitorPrinting();
		}
	} else {
		alert("Applet not loaded!");
	}
}

// *Note:  monitorAppending2() still works but is too complicated and
// outdated.  Instead create a JavaScript  function called
// "jzebraDoneAppending()" and handle your next steps there.
function monitorAppending2() {
	var applet = document.jZebra;
	if (applet != null) {
		if (!applet.isDoneAppending()) {
			window.setTimeout('monitorAppending2()', 100);
		} else {
			applet.printPS(); // Don't print until all of the image data has been appended

			// *Note:  monitorPrinting() still works but is too complicated and
			// outdated.  Instead create a JavaScript  function called
			// "jzebraDonePrinting()" and handle your next steps there.
			monitorPrinting();
		}
	} else {
		alert("Applet not loaded!");
	}
}

// *Note:  monitorAppending3() still works but is too complicated and
// outdated.  Instead create a JavaScript  function called
// "jzebraDoneAppending()" and handle your next steps there.
function monitorAppending3() {
	var applet = document.jZebra;
	if (applet != null) {
		if (!applet.isDoneAppending()) {
			window.setTimeout('monitorAppending3()', 100);
		} else {
			applet.printHTML(); // Don't print until all of the image data has been appended


			// *Note:  monitorPrinting() still works but is too complicated and
			// outdated.  Instead create a JavaScript  function called
			// "jzebraDonePrinting()" and handle your next steps there.
			monitorPrinting();
		}
	} else {
		alert("Applet not loaded!");
	}
}

function useDefaultPrinter() {
	var applet = document.jZebra;
	if (applet != null) {
		// Searches for default printer
		applet.findPrinter();
	}

	monitorFinding();
}

function jzebraReady() {
	// Change title to reflect version
	var applet = document.jZebra;
	var title = document.getElementById("title");
	if (applet != null) {
		title.innerHTML = title.innerHTML + " " + applet.getVersion();
		document.getElementById("content").style.background = "#F0F0F0";
	}
}

/**
 * By default, jZebra prevents multiple instances of the applet's main
 * JavaScript listener thread to start up.  This can cause problems if
 * you have jZebra loaded on multiple pages at once.
 *
 * The downside to this is Internet Explorer has a tendency to initilize the
 * applet multiple times, so use this setting with care.
 */
function allowMultiple() {
	var applet = document.jZebra;
	if (applet != null) {
		var multiple = applet.getAllowMultipleInstances();
		applet.allowMultipleInstances(!multiple);
		alert('Allowing of multiple applet instances set to "' + !multiple + '"');
	}
}

function printPage() {
	$("#content").html2canvas({
		canvas: hidden_screenshot,
		onrendered: function () {
			printBase64Image($("canvas")[0].toDataURL('image/png'));
		}
	});
}

function printBase64Image(base64data) {
	var applet = document.jZebra;
	if (applet != null) {
		applet.findPrinter("\\{dummy printer name for listing\\}");
		while (!applet.isDoneFinding()) {
			// Note, endless while loops are bad practice.
		}

		var printers = applet.getPrinters().split(",");
		for (i in printers) {
			if (printers[i].indexOf("Microsoft XPS") != -1 ||
				printers[i].indexOf("PDF") != -1) {
				applet.setPrinter(i);
			}
		}

		// No suitable printer found, exit
		if (applet.getPrinter() == null) {
			alert("Could not find a suitable printer for printing an image.");
			return;
		}

		// Optional, set up custom page size.  These only work for PostScript printing.
		// setPaperSize() must be called before setAutoSize(), setOrientation(), etc.
		applet.setPaperSize("8.5in", "11.0in"); // US Letter
		applet.setAutoSize(true);
		applet.appendImage(base64data);
	}

	// Very important for images, uses printPS() insetad of print()
	// *Note:  monitorAppending2() still works but is too complicated and
	// outdated.  Instead create a JavaScript  function called
	// "jzebraDoneAppending()" and handle your next steps there.
	monitorAppending2();
}

function logFeatures() {
	if (document.jZebra != null) {
		var applet = document.jZebra;
		var logging = applet.getLogPostScriptFeatures();
		applet.setLogPostScriptFeatures(!logging);
		alert('Logging of PostScript printer capabilities to console set to "' + !logging + '"');
	}
}

function useAlternatePrinting() {
	var applet = document.jZebra;
	if (applet != null) {
		var alternate = applet.isAlternatePrinting();
		applet.useAlternatePrinting(!alternate);
		alert('Alternate CUPS printing set to "' + !alternate + '"');
	}
}
