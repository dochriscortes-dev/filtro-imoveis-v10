<?php
// templates/search-interface.php

// Fetch terms
$cities = get_terms( array( 'taxonomy' => 'cidade', 'hide_empty' => false ) );
$neighborhoods = get_terms( array( 'taxonomy' => 'bairro', 'hide_empty' => false ) );
$property_types = get_terms( array( 'taxonomy' => 'tipo_imovel', 'hide_empty' => false ) );

// Error handling if taxonomies don't exist (avoid fatal errors on empty init)
if ( is_wp_error( $cities ) ) $cities = array();
if ( is_wp_error( $neighborhoods ) ) $neighborhoods = array();
if ( is_wp_error( $property_types ) ) $property_types = array();
?>

<!-- Sticky Bar -->
<div id="apaf-search-bar-container">
    <div id="apaf-search-bar">
        <!-- Toggle: Buy / Rent -->
        <div class="apaf-toggle-group">
            <label>
                <input type="radio" name="apaf_pretensao" value="venda" checked>
                <span>Comprar</span>
            </label>
            <label>
                <input type="radio" name="apaf_pretensao" value="aluguel">
                <span>Alugar</span>
            </label>
        </div>

        <!-- City Dropdown -->
        <div class="apaf-select-group">
            <select id="apaf-city" name="apaf_city" class="apaf-select2" style="width: 100%; min-width: 150px;">
                <option value="">Cidade</option>
                <?php foreach ( $cities as $city ) : ?>
                    <option value="<?php echo esc_attr( $city->slug ); ?>"><?php echo esc_html( $city->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Neighborhood Dropdown -->
        <div class="apaf-select-group">
            <select id="apaf-neighborhood" name="apaf_neighborhood" class="apaf-select2" disabled style="width: 100%; min-width: 150px;">
                <option value="">Bairro</option>
                <?php foreach ( $neighborhoods as $neigh ) : ?>
                    <!-- We output all. Filtering could be done via data-city if relation existed, but we'll just enable it. -->
                    <option value="<?php echo esc_attr( $neigh->slug ); ?>"><?php echo esc_html( $neigh->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Advanced Filters Link -->
        <div class="apaf-advanced-link">
            <a href="#" id="apaf-open-filters">
                <span class="dashicons dashicons-filter"></span> Filtros Avançados
            </a>
        </div>

        <!-- Search Button -->
        <div class="apaf-search-action">
            <button id="apaf-search-btn" type="button">BUSCAR</button>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="apaf-modal-overlay" style="display: none;">
    <div id="apaf-modal-content">
        <div class="apaf-modal-header">
            <h3>Filtros Avançados</h3>
            <button id="apaf-close-modal" type="button">&times;</button>
        </div>

        <div class="apaf-modal-body">
            <!-- Row 1: Zone -->
            <div class="apaf-filter-row">
                <label>Zona</label>
                <select id="apaf-zone" name="apaf_zone" class="apaf-select2-simple" style="width: 100%;">
                    <option value="">Todas</option>
                    <option value="urbana">Urbana</option>
                    <option value="rural">Rural</option>
                </select>
            </div>

            <!-- Row 2: Property Types (Grid) -->
            <div class="apaf-filter-row">
                <label>Tipo de Imóvel</label>
                <div class="apaf-checkbox-grid">
                    <?php foreach ( $property_types as $type ) : ?>
                        <label class="apaf-checkbox-card">
                            <input type="checkbox" name="apaf_property_type[]" value="<?php echo esc_attr( $type->slug ); ?>">
                            <span><?php echo esc_html( $type->name ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Row 3: Numeric Specs -->
            <div class="apaf-filter-row apaf-specs-row">
                <div class="apaf-spec-col">
                    <label>Quartos</label>
                    <div class="apaf-num-buttons" data-field="quartos">
                        <?php for($i=1; $i<=4; $i++): ?>
                            <button type="button" data-value="<?php echo $i; ?>"><?php echo $i . ($i==4 ? '+' : ''); ?></button>
                        <?php endfor; ?>
                        <input type="hidden" name="apaf_quartos" value="">
                    </div>
                </div>
                <div class="apaf-spec-col">
                    <label>Banheiros</label>
                    <div class="apaf-num-buttons" data-field="banheiros">
                         <?php for($i=1; $i<=4; $i++): ?>
                            <button type="button" data-value="<?php echo $i; ?>"><?php echo $i . ($i==4 ? '+' : ''); ?></button>
                        <?php endfor; ?>
                        <input type="hidden" name="apaf_banheiros" value="">
                    </div>
                </div>
                <div class="apaf-spec-col">
                    <label>Vagas</label>
                    <div class="apaf-num-buttons" data-field="vagas">
                         <?php for($i=1; $i<=4; $i++): ?>
                            <button type="button" data-value="<?php echo $i; ?>"><?php echo $i . ($i==4 ? '+' : ''); ?></button>
                        <?php endfor; ?>
                        <input type="hidden" name="apaf_vagas" value="">
                    </div>
                </div>
            </div>

            <!-- Row 4: Price Range -->
            <div class="apaf-filter-row">
                <label>Faixa de Preço</label>
                <div id="apaf-price-slider"></div>
                <div class="apaf-price-inputs">
                    <input type="number" id="apaf-min-price" name="apaf_min_price" placeholder="Min">
                    <input type="number" id="apaf-max-price" name="apaf_max_price" placeholder="Max">
                </div>
            </div>

            <!-- Row 5: Financing -->
            <div class="apaf-filter-row">
                <label class="apaf-toggle-check">
                    <input type="checkbox" name="apaf_financing" value="1">
                    <span class="slider round"></span>
                    <span class="label-text">Aceita Financiamento</span>
                </label>
            </div>
        </div>

        <div class="apaf-modal-footer">
            <button id="apaf-apply-filters" type="button">APLICAR FILTROS</button>
        </div>
    </div>
</div>

<!-- Results Grid -->
<div id="apaf-results-wrapper">
    <div id="apaf-results-grid" class="apaf-grid">
        <!-- Results will be injected here -->
    </div>
</div>
