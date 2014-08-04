/**
 * Dit script voegt functionaliteit toe aan het barsysteem.
 */
$(function () {

    /*************************************************************************************************/
    /* Clock
    /*************************************************************************************************/

    $("#clock").each(function () {

        function addLeading(number) {

            if ((number + "").length == 2)
                return number;

            return "0" + number;

        }

        function update() {

            var currentDate = new Date();
            $("#clock").html(
                    addLeading(currentDate.getHours()) + ":" +
                    addLeading(currentDate.getMinutes()) + ":" +
                    addLeading(currentDate.getSeconds())
            );

        }

        update();
        setInterval(update, 1000);

    });

    /*************************************************************************************************/
    /* End Clock
     /*************************************************************************************************/

    /**
     * Deze persoon is geselecteerd, dit wordt oa. gebruikt bij de invoer van bestellingen, inleg en laden van de bestellingen van die persoon.
     */
    var selectedPerson;


    var beheer = true;

    /**
     * Dit is de lijst met bestellingen.
     * @type {{}} deze lijst mapt het productId naar het aantal dat er besteld zijn. Bijv 1=>2, dit betekent bijvoorbeeld dat er twee bier is besteld.
     */
    var bestelLijst = {};

    /**
     * Hierin zit de oude bestelling, in het geval we een bestelling verwerken.
     */
    var oudeBestelling;


    $.extend($.tablesorter.themes.bootstrap, {
        // these classes are added to the table. To see other table classes available,
        // look here: http://twitter.github.com/bootstrap/base-css.html#tables
        table: 'table table-bordered',
        caption: 'caption',
        header: 'bootstrap-header', // give the header a gradient background
        footerRow: '',
        footerCells: '',
        icons: '', // add "icon-white" to make them white; this icon class is added to the <i> in the header
        sortNone: 'bootstrap-icon-unsorted',
        sortAsc: 'icon-chevron-up glyphicon glyphicon-chevron-up',     // includes classes for Bootstrap v2 & v3
        sortDesc: 'icon-chevron-down glyphicon glyphicon-chevron-down', // includes classes for Bootstrap v2 & v3
        active: '', // applied when column is sorted
        hover: '', // use custom css here - bootstrap class may not override it
        filterRow: '', // filter row class
        even: '', // odd row zebra striping
        odd: ''  // even row zebra striping
    });
    $("#besteLijstBeheer").tablesorter({
        theme: "bootstrap",
        widthFixed: true,
        sortList: [
            [1, 1]
        ],
        headerTemplate: '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!

        // widget code contained in the jquery.tablesorter.widgets.js file
        // use the zebra stripe widget if you plan on hiding any rows (filter widget)
        widgets: [ "uitheme", "zebra" ],

        widgetOptions: {
            // using the default zebra striping class name, so it actually isn't included in the theme variable above
            // this is ONLY needed for bootstrap theming if you are using the filter widget, because rows are hidden
            zebra: ["even", "odd"],

            // reset filters button
            filter_reset: ".reset"

            // set the uitheme widget to use the bootstrap theme class names
            // this is no longer required, if theme is set
            // ,uitheme : "bootstrap"

        }

    });

    function zetInTabel(persoon) {
        var naam = persoon.naam;
        $("#selectieTabel > tbody").append("<tr id='persoon" + persoon.socCieId + "'><td>" + persoon.bijnaam + "</td><td>" + naam + "</td></tr>");
        $("#persoon" + persoon.socCieId).click(function () {
            cancel();
            $.ajax({
                url: "ajax.php",
                method: "POST",
                data: {"saldoSocCieId": persoon.socCieId}
            }).done(function (data) {
                persoon.saldo = 1 * data;
            });
            selectedPerson = persoon;

            zetBericht("Geselecteerde persoon: " + naam + " | Saldo: " + saldoStr(persoon.saldo), persoon.saldo >= 0 ? 'success' : 'danger');

            $("#invoerveld").trigger("click");
            $("#besteLijstBeheerLaadPersoon").html("Laad bestellingen van: " + naam);
            $("#persoonInput").val(null);
            updateOnKeyPress();
            resetTeller();
            resetLijst();

        });
    }

    function resetLijst() {
        bestelLijst = {};
        zetBestelLijstGoed();
    }

    function resetTeller() {
        $("#aantalInput")[0].value = null;
    }

    function zetProductInLijst(product) {
        $("#bestelKnoppenLijst").append("<button type='button' class='btn btn-bestel btn-default' id='bestelKnop" + product.productId + "'>" + product.beschrijving + "<br />" +
            saldoStr(product.prijs) + "</button>");
        $("#bestelKnop" + product.productId).click(function () {
            var aantal = $("#aantalInput")[0].value;
            if (aantal == "" || aantal == 0) {
                aantal = 1;
            }
            if (aantal == "-") {
                aantal = -1;
            }
            if (product.productId in bestelLijst) {
                var nieuw = bestelLijst[product.productId] + (1 * aantal);
                if (nieuw <= 0) {
                    delete bestelLijst[product.productId];
                }
                else {
                    bestelLijst[product.productId] = nieuw;
                }
            } else if (aantal > 0) {
                bestelLijst[product.productId] = (1 * aantal);
            }
            resetTeller();
            zetBestelLijstGoed();
        })

    }

    function bestelTotaal() {
        var bestelTotaal = 0;
        for (key in bestelLijst) {
            bestelTotaal += 1.0 * bestelLijst[key] * producten[key].prijs;
        }
        return bestelTotaal;
    }

    function zetBestelLijstGoed() {
        $(".bestelLijst").empty();
        var totaal = bestelTotaal();
        var teller = 0;
        for (key in bestelLijst) {
            var aantal = bestelLijst[key];
            if (producten[key].prijs < 0) aantal = saldoStr(aantal);
            $("#bestelLijst" + (teller % 3 + 1)).append("<li class=" + key + ">" + aantal + "&#09" + producten[key].beschrijving + "</li>");
            teller++;
        }

        // Add onclick remove
        $("#bestelLijstDiv li").click(function () {

            var key = $(this).attr("class");
            delete bestelLijst[key];

            zetBestelLijstGoed();

        });

        if (oudeBestelling) {

        }

        if (selectedPerson) {
            $("#huidigSaldo").html(saldoStr(selectedPerson.saldo));
            $("#nieuwSaldo").html(saldoStr(selectedPerson.saldo - totaal));
        } else {
            $("#huidigSaldo").html("<span>-</span>");
            $("#nieuwSaldo").html("<span>-</span>");
        }
        $("#totaalBestelling").html(saldoStr(totaal));
    }

    function saldoStr(saldo) {
        var achterKomma = Math.abs(saldo % 100);
        if (achterKomma == 0) achterKomma = "00";
        else if (achterKomma < 10) achterKomma = "0" + achterKomma;
        if (saldo > -100 && saldo < 0) return "€-0," + achterKomma;
        return "€" + (saldo - (saldo % 100)) / 100 + "," + achterKomma;
    }

    function zetBericht(bericht, type) {
        $("#waarschuwing").removeClass().addClass("alert alert-" + type).html(bericht);
    }

    function zetWaarschuwing(bericht) {
        zetBericht(bericht, 'warning');
    }

    function zetInfo(bericht) {
        zetBericht(bericht, 'info');
    }

    var personen = {};
    var producten = {};

    $.ajax({
        url: "ajax.php",
        method: "POST",
        data: {"personen": "waar"}
    })
        .done(function (data) {
            personen = $.parseJSON(data);
            updateOnKeyPress();
        });

    $.ajax({
        url: "ajax.php",
        method: "POST",
        data: {"producten": "waar"}
    })
        .done(function (data) {
            productenTemp = $.parseJSON(data);
            var sorteerbaar = [];
            $.each(productenTemp, function () {
                sorteerbaar.push([this, this.prioriteit]);
                producten[this.productId] = this;
            });
            sorteerbaar.sort(function (a, b) {
                return b[1] - a[1];
            });
            $.each(sorteerbaar, function () {
                zetProductInLijst(this[0])
            });
        });

    function updateOnKeyPress() {
        var item = new RegExp($("#persoonInput").val(), "gi");
        var output = new Array();
        $("#selectieTabel > tbody").empty();
        $.each(personen, function () {

            if (this.bijnaam.match(item) || this.naam.match(item)) {
                output.push(this);
                zetInTabel(this);
            }

        });
    }

    $("#keyboardToggle").click(function () {
        $("#keyboardContainer").toggle();
    });

    $("#persoonInput").bind("change keyup", updateOnKeyPress);

    /*************************************************************************************************/
    /* Order keypad
    /*************************************************************************************************/

    for (i = 0; i < 10; i++) {
        (function (j) {
            $("#knop" + i).click(function () {
                if ($("#aantalInput")[0].value == "0") resetTeller();
                $("#aantalInput")[0].value = $("#aantalInput")[0].value + "" + j;
            });
        })(i);
    }

    $("#knopC").click(function () {
        if ($(isNaN("#aantalInput"))[0].value) resetTeller();
        else {
            $("#aantalInput")[0].value = ($("#aantalInput")[0].value - $("#aantalInput")[0].value % 10) / 10;
            if ($("#aantalInput")[0].value == "0") resetTeller();
        }
    })

    $("#knop-").click(function () {
        $("#aantalInput")[0].value = $("#aantalInput")[0].value * -1;
        if ($("#aantalInput")[0].value == "0") {
            resetTeller();
            $("#aantalInput")[0].value = "-";
        }
    })

    $("#knopConfirm").each(function() {
	
		// Set current submiting state on false
		var submitting = false;
	
		$(this).click(function () {
		
			if (selectedPerson && bestelTotaal() != 0) {
			
				// Set submitting state on true
				submitting = true;
			
				var result = {};
				result["bestelLijst"] = bestelLijst;
				result["bestelTotaal"] = bestelTotaal();
				result["persoon"] = selectedPerson;
				
				// If update of old order us that data
				if (oudeBestelling) result["oudeBestelling"] = oudeBestelling;
				
				$.ajax({
					url: "ajax.php",
					method: "POST",
					data: {"bestelling": JSON.stringify(result)}
				}).done(function (data) {
					if (data == "1") {
						//succes! de bestelling is goed verwerkt
						cancel();
					} else {
						zetBericht("Er gaat iets verkeert met de bestelling, hij is niet verwerkt!", "danger");
					}
				}).always(function() {
				
					// After AJAX always set submitting on false
					submitting = false;
				
				});

			} else if (!selectedPerson) {
				zetBericht("Geen geldig persoon geselecteerd!", "danger");
			} else if (bestelTotaal() == 0) {
				zetBericht("Geen bestelling ingevoerd!", "danger");
			}
		
		});
		
    });

    /*************************************************************************************************/
    /* Keyboard
    /*************************************************************************************************/

	$('#keyboard li').not('.spacer').click(function () {
		var $this = $(this),
			character = $this.html().toLowerCase(); // If it's a lowercase letter, nothing happens to this variable

		// Delete
		if ($this.hasClass('delete')) {
			$("#persoonInput").val($("#persoonInput").val().slice(0, -1)).focus();
			updateOnKeyPress();
			return false;
		} else if ($this.hasClass('leeg')) {
			$("#persoonInput").val('').focus();
			updateOnKeyPress();
			return false;
		}

		if ($this.hasClass('space')) character = ' ';

		// Add the character
		$("#persoonInput").val($("#persoonInput").val() + character).focus();
		updateOnKeyPress();
	});

    $("#knopCancel").click(cancel);

    function cancel() {
        selectedPerson = null;
        oudeBestelling = null;
        resetLijst();
        resetTeller();
        zetInfo("Geen persoon geselecteerd");
        $("#besteLijstBeheerContent tbody").empty();
        $("#besteLijstBeheerLaadPersoon").empty();
        $("#besteLijstBeheerLaadPersoon").append("Laad bestellingen van: -");
        $("#persoonselectieVeld").trigger("click");
    }

    cancel();


    $("#krijgBestellingen").click(function () {
        var aantal = "alles";
        if ($("#eenPersoon").hasClass("btn-primary")) {
            aantal = selectedPerson.socCieId;
        }
        $.ajax({
            url: "ajax.php",
            method: "POST",
            data: {"laadLaatste": "waar", "begin": $("#beginDatum").val(), "eind": $("#eindDatum").val(), "aantal": aantal }
        }).done(function (data) {
            zetOudeBestellingen($.parseJSON(data));
        });
    });

    /**
     * Deze functie zet oude bestellingen in de tab 'bestellingen'.
     * Het voegt functies toe om bestellingen te bewerken op persoon en inhoud.
     * Het geeft tevens de mogelijkheid bestellingen te verwijderen.
     * @param bestellingen een lijst in JSON met allen bestellingen.
     */
    function zetOudeBestellingen(bestellingen) {
        $("#besteLijstBeheerContent tbody").empty();
        $.each(bestellingen, function (item) {
            var bestelling = bestellingen[item];
            var bestel = [];
            for (key in bestelling.bestelLijst) {
                bestel.push(bestelling.bestelLijst[key] + " " + producten[key].beschrijving);
            }
            bestel = bestel.join(", ");
            $("#besteLijstBeheerContent tbody").append("<tr id='tabelRijBeheerLijst" + item + "'><td>" + personen[bestelling.persoon].naam + "</td><td>"
                + bestelling.tijd + "</td><td>" + saldoStr(bestelling.bestelTotaal) + "</td><td>" + bestel + "</td>" +
                "<td><div class='btn-group'><button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown'>Opties <span class='caret'></span></button>" +
                "<ul class='dropdown-menu dropdown-menu-right' role='menu'>" +
                "<li><a href='#' id='anderePersoon" + item + "'>Zet bestelling op andere persoon</a></li>" +
                "<li><a href='#' id='bewerkInhoud" + item + "'>Bewerk inhoud bestelling</a></li>" +
                "<li><a href='#' id='verwijderBestelling" + item + "'>Verwijder bestelling</a></li>" +
                "</ul></div></td></tr>");

            $("#besteLijstBeheer").trigger("update")

            $("#anderePersoon" + item).click(function () {
                //todo
            });
            $("#bewerkInhoud" + item).click(function () {
                zetWaarschuwing("U bewerkt een bestelling!");
                //console.log(bestelling);
                bestelLijst = bestelling.bestelLijst;
                oudeBestelling = bestelling;
                selectedPerson = personen[bestelling.persoon]
                resetTeller();
                zetBestelLijstGoed();
                $("#invoerveld").trigger("click");
            });
            $("#verwijderBestelling" + item).click(function () {
                if (confirm("Weet u zeker dat u de bestelling van " + bestel + " op: " + bestelling.tijd + " wilt verwijderen?")) {
                    $.ajax({
                        url: "ajax.php",
                        method: "POST",
                        data: {"verwijderBestelling": JSON.stringify(bestelling)}
                    }).done(function (data) {
                        if (data = "1") {
                            $("#tabelRijBeheerLijst" + item).remove();
                        }
                    });
                }
            });
        })
    }

    $("#eenPersoon").click(function () {
        $("#allePersonen").removeClass("btn-primary");
        $("#eenPersoon").addClass("btn-primary");
    });

    $("#allePersonen").click(function () {
        $("#allePersonen").addClass("btn-primary");
        $("#eenPersoon").removeClass("btn-primary");
    })

    $(".clearKruisje").click(function () {
        $(this).prev("input").val("");
    })

    $('.input-daterange').datepicker({
        format: "dd MM yyyy",
        language: "nl",
        todayBtn: "linked",
        autoclose: true,
        todayHighlight: true,
        beforeShowDay: function (date) {
            if (date.getMonth() == (new Date()).getMonth())
                switch (date.getDate()) {
                    case 4:
                        return {
                            tooltip: 'Example tooltip',
                            classes: 'active'
                        };
                    case 8:
                        return false;
                    case 12:
                        return "green";
                }
        }
    });

    /*************************************************************************************************/
    /* Beheer
    /*************************************************************************************************/

    $("#laadProducten").click(function () {
		
		$("#productBeheerLijst").empty();
	
        $.each(producten, function (id) {
		
            var product = producten[id];
            $("#productBeheerLijst").append("<li class='list-group-item' id='productBeheerLijst" + product.productId + "'>" + product.beschrijving + "</li>");
            
			$("#productBeheerLijst" + product.productId).click(setProduct(product));

            function setProduct(product) {

            }
			
        });
		
    });
	
	$("#laadGrootboekInvoer").click(function() {
	
		var button = $(this);
	
		$.ajax({
			url: "ajax.php?q=grootboek",
			method: "GET",
			dataType: "json",
			success: function(data) {
		
				button.removeClass("btn-default").addClass("btn-primary");
				
				html = '';
				
				console.log(data);
				$.each(data, function(week) {
				
					html += '<h2>' + week.title + '</h2>';
				
				});
				
				$("#grootboekInvoer").html(html).removeClass("hidden");
			
			}
		});
	
	});

});