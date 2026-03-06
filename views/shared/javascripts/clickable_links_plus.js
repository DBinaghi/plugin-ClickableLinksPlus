/**
 * Clickable Links Plus — client-side processing
 *
 * Sostituisce l'approccio precedente basato su document.write() inline.
 * Attende il DOMContentLoaded, poi cerca tutti gli span.linkify-target
 * emessi da filterDisplay() e applica linkifyHtml() su ciascuno.
 *
 * Le opzioni di configurazione vengono lette dalla variabile globale
 * ClickableLinksPlus, iniettata una sola volta da hookPublicHead().
 */
document.addEventListener('DOMContentLoaded', function () {

	// Verifica che la variabile di configurazione e la libreria siano disponibili
	if (typeof ClickableLinksPlus === 'undefined' || typeof linkifyHtml === 'undefined') {
		return;
	}

	var cfg = ClickableLinksPlus;

	// Costruisce l'oggetto opzioni per Linkify
	var options = {
		attributes: {
			rel: 'nofollow',
			title: cfg.tooltip || '',
		},
		// target="_blank" solo per link esterni; Linkify lo gestisce internamente
		// tramite la proprietà target nella callback degli attributi
		target: function (href, type) {
			if (type === 'url') {
				// Link interni (stesso host) si aprono nella stessa scheda
				try {
					var linkHost = new URL(href).hostname;
					var homeHost = window.location.hostname;
					return linkHost === homeHost ? '_self' : '_blank';
				} catch (e) {
					// URL relativa o malformata: trattata come interna
					return '_self';
				}
			}
			return null;
		},
		format: {
			url: function (value) {
				if (cfg.labelLength > 0 && value.length > cfg.labelLength) {
					return value.slice(0, cfg.labelLength) + '\u2026'; // …
				}
				return value;
			}
		},
		ignoreTags: ['a'],
		validate: {
			url: function (value) {
				if (cfg.wellformatted) {
					return /^(http|ftp)s?:\/\//.test(value);
				}
				return true;
			}
		}
	};

	// Processa ogni span marcato da filterDisplay()
	var targets = document.querySelectorAll('span.linkify-target');
	for (var i = 0; i < targets.length; i++) {
		var el = targets[i];

		// textContent è già il testo plain (htmlspecialchars è stato applicato lato PHP,
		// il browser lo ha già decodificato nel DOM — qui riceviamo il testo originale)
		var plainText = el.textContent;

		// Sostituisce il contenuto dello span con l'HTML linkificato
		el.innerHTML = linkifyHtml(plainText, options);
	}
});
