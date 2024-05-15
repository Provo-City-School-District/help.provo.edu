function split(val) {
	return val.split(/,\s*/);
}

function extractLast(term) {
	return split(term).pop();
}

$("#cc_emails").on("input", function () {
	const new_value = extractLast($(this).val());
	$("#cc_emails").autocomplete({
	  source: function (request, response) {
		$.ajax({
			url: "/ajax/email_matches_ldap.php",
			method: "GET",
			data: {
				input: new_value,
		  	},
		  	success: function (data, textStatus, xhr) {
				let mappedResults = $.map(data, function (item) {
			  	let itemLocation = item.location ? item.location : "unknown";
			  	return $.extend(item, {
					label:
				  		item.firstName +
				  		" " +
				  		item.lastName +
				  		" (" +
				  		itemLocation +
				  		")",
					value: item.email,
			  		});
				});
				response(mappedResults);
		  	},
		  	error: function () {
				alert("Error: Autocomplete AJAX call failed");
		  	},
		});
	},
	minLength: 3,
	search: function () {
		const term = extractLast(this.value);
		if (term.length < 1) {
			return false;
		}
	},
	focus: function () {
		// prevent value inserted on focus
		return false;
	},
	select: function (event, ui) {
		let terms = split(this.value);
  
		terms.pop();
		terms.push(ui.item.value);
		terms.push("");
  
		this.value = terms.join(",");
		return false;
	},
	});
});
  
$("#bcc_emails").on("input", function () {
	const new_value = extractLast($(this).val());
	$("#bcc_emails").autocomplete({
	  source: function (request, response) {
		$.ajax({
			url: "/ajax/email_matches_ldap.php",
			method: "GET",
			data: {
				input: new_value,
		  	},
		  	success: function (data, textStatus, xhr) {
				let mappedResults = $.map(data, function (item) {
			  	let itemLocation = item.location ? item.location : "unknown";
			  	return $.extend(item, {
					label:
				  		item.firstName +
				  		" " +
				  		item.lastName +
				  		" (" +
				  		itemLocation +
				  		")",
					value: item.email,
			  		});
				});
				response(mappedResults);
		  	},
		  	error: function () {
				alert("Error: Autocomplete AJAX call failed");
		  	},
		});
	},
	minLength: 3,
	search: function () {
		const term = extractLast(this.value);
		if (term.length < 1) {
			return false;
		}
	},
	focus: function () {
		// prevent value inserted on focus
		return false;
	},
	select: function (event, ui) {
		let terms = split(this.value);
  
		terms.pop();
		terms.push(ui.item.value);
		terms.push("");
  
		this.value = terms.join(",");
		return false;
	},
	});
});

// Prevent Double Submits
document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", (e) => {
        // Prevent if already submitting
        if (form.classList.contains("is-submitting")) {
            e.preventDefault();
        }

        // Add class to hook our visual indicator on
        form.classList.add("is-submitting");
    });
});

$("#client").on("input", function() {
    const new_value = extractLast($(this).val());
    $("#client").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "/ajax/name_search_ldap.php",
                method: "GET",
                data: {
                    name: new_value,
                },
                success: function(data, textStatus, xhr) {
                    let mappedResults = $.map(data, function(item) {
                        let itemLocation = item.location ? item.location : "unknown";
                        return $.extend(item, {
                            label: item.firstName +
                                " " +
                                item.lastName +
                                " (" +
                                itemLocation +
                                ")",
                            value: item.username,
                        });
                    });
                    response(mappedResults);
                },
                error: function() {
                    alert("Error: Autocomplete AJAX call failed");
                },
            });
        },
        minLength: 3
    });
});