jQuery(document).ready(function($) {

    // --- Initialize UI Components ---

    // Select2
    $('.apaf-select2').select2({
        minimumResultsForSearch: 10 // Hide search if few items
    });
    $('.apaf-select2-simple').select2({
        minimumResultsForSearch: Infinity
    });

    // noUiSlider for Price
    var slider = document.getElementById('apaf-price-slider');
    if (slider) {
        noUiSlider.create(slider, {
            start: [0, 5000000], // Default range, should be dynamic ideally but fixed for init
            connect: true,
            range: {
                'min': 0,
                'max': 10000000
            },
            step: 1000,
            format: {
                to: function (value) {
                    return Math.round(value);
                },
                from: function (value) {
                    return Number(value);
                }
            }
        });

        var minInput = document.getElementById('apaf-min-price');
        var maxInput = document.getElementById('apaf-max-price');

        slider.noUiSlider.on('update', function (values, handle) {
            var value = values[handle];
            if (handle) {
                maxInput.value = value;
            } else {
                minInput.value = value;
            }
        });

        minInput.addEventListener('change', function () {
            slider.noUiSlider.set([this.value, null]);
        });

        maxInput.addEventListener('change', function () {
            slider.noUiSlider.set([null, this.value]);
        });
    }

    // --- Interaction Logic ---

    // Toggle Buy/Rent logic (visual update if needed, but radio works naturally)

    // City -> Neighborhood Dependency
    // Since we output all neighborhoods, we need to filter them if possible, or just enable.
    // The requirement says "Disabled until City is selected".
    $('#apaf-city').on('change', function() {
        var city = $(this).val();
        if (city) {
            $('#apaf-neighborhood').prop('disabled', false);
            // If there were a data-city mapping, we would filter here.
            // Example: $("#apaf-neighborhood option").hide().filter('[data-city="'+city+'"]').show();
            // Since we don't have that mapping from the prompt, we just enable it.
        } else {
            $('#apaf-neighborhood').prop('disabled', true).val('').trigger('change');
        }
    });

    // Modal Open/Close
    $('#apaf-open-filters').on('click', function(e) {
        e.preventDefault();
        $('#apaf-modal-overlay').fadeIn(200);
    });

    $('#apaf-close-modal, #apaf-modal-overlay').on('click', function(e) {
        if (e.target === this) {
             $('#apaf-modal-overlay').fadeOut(200);
        }
    });

    // Square Buttons Logic
    $('.apaf-num-buttons button').on('click', function() {
        var parent = $(this).closest('.apaf-num-buttons');
        var val = $(this).data('value');

        // Check if already active - if so, deselect
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            parent.find('input').val('');
        } else {
            parent.find('button').removeClass('active');
            $(this).addClass('active');
            parent.find('input').val(val);
        }
    });

    // Apply Filters Button
    $('#apaf-apply-filters').on('click', function() {
        $('#apaf-modal-overlay').fadeOut(200);
        triggerSearch();
    });

    // Search Button
    $('#apaf-search-btn').on('click', function() {
        triggerSearch();
    });

    // --- AJAX Search ---

    function triggerSearch() {
        var $btn = $('#apaf-search-btn');
        $btn.text('Buscando...').prop('disabled', true);

        // Gather Data
        var data = {
            action: 'apaf_filter_properties',
            nonce: apaf_vars.nonce,
            pretensao: $('input[name="apaf_pretensao"]:checked').val(),
            cidade: $('#apaf-city').val(),
            bairro: $('#apaf-neighborhood').val(),
            zone: $('#apaf-zone').val(),
            property_types: [],
            quartos: $('input[name="apaf_quartos"]').val(),
            banheiros: $('input[name="apaf_banheiros"]').val(),
            vagas: $('input[name="apaf_vagas"]').val(),
            min_price: $('#apaf-min-price').val(),
            max_price: $('#apaf-max-price').val(),
            financing: $('input[name="apaf_financing"]').is(':checked') ? 1 : 0
        };

        // Gather property types
        $('input[name="apaf_property_type[]"]:checked').each(function() {
            data.property_types.push($(this).val());
        });

        // AJAX Request
        $.ajax({
            url: apaf_vars.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                $btn.text('BUSCAR').prop('disabled', false);
                if (response.success) {
                    $('#apaf-results-grid').html(response.data.html);
                } else {
                    $('#apaf-results-grid').html('<p>Erro na busca.</p>');
                }
            },
            error: function() {
                $btn.text('BUSCAR').prop('disabled', false);
                $('#apaf-results-grid').html('<p>Erro de conexão.</p>');
            }
        });
    }

});
