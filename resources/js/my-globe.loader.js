{
	
	let html = `
<hyper-globe id="my-globe" data-location="0 0" data-version="1" style="--preview-color: #000000; max-width: 720px; --globe-scale: 0.8; --globe-damping: 0.5; --map-density: 0.5; --map-height: 0; --point-size: 1; --point-color: #999999; --backside-opacity: 0.2; --backside-transition: 0.1; --marker-size: 1; --title-position: 0 -1; --title-padding: 1; --text-color: #999999; --text-size: 1; --text-height: 1.1; --text-padding: 0.5; --line-color: #999999; --line-thickness: 1; --line-offset: 1;">
<a slot="markers" class="globe-marker" data-location="47 -122"></a></hyper-globe>
	`;
	
	let css = ``;
	
	let script = ``;
	
	
	if ( ! self.confGlobe ) {
		// get the script element
		let elem = document.currentScript;
		if ( elem && elem.isConnected && elem.closest('body') && elem.getAttribute('src') ) {
			
			// get baseurl from script src
			let url = new URL( elem.getAttribute('src'), self.location.href ).href;
			if ( url.startsWith('http') ) {		
				let baseurl = url.substr(0, url.lastIndexOf('/')+1);		
				
				// import hyper globe module once
				if ( ! self.hyperGlobe ) {
					self.hyperGlobe = true;
					import( baseurl + 'hyper-globe.min.js' );
				}			
				
				// inject html
				html = html.replace('<hyper-globe ', `<hyper-globe data-baseurl="${baseurl}" `);
				elem.insertAdjacentHTML('afterend', html);
				if (css)	elem.nextElementSibling.insertAdjacentHTML('afterend', `<style>${css}</style>`);
				if (script) elem.nextElementSibling.addEventListener('complete', new Function(script));
				
			} else {
				console.error('This script was used in the wrong way. It must be loaded via the https: or http: protocol.');
			}
			
		} else {
			console.error('This script was used in the wrong way. It must be a non-module script inside the body element.');
		}
	}	
	
}