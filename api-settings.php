<?php
/*
* Plugin Name: PrintshopCreator API-Connect
* Plugin URI: https://printshopcreator.de/api-einstellungen
* Description: Mit dieser App können Sie PrintshopCreator Kalkulationen und Produktkonfiguratoren direkt in Ihre WordPress-Website einbinden. With this app you can embed PrintshopCreator calculations and product configurators directly into your WordPress website.
* Version: 1.0.2
* Author: PrintshopCreator GmbH
* Author URI: https://printshopcreator.de
* License: GPL2
*/
include_once 'shortcodes.php';
// Erstelle neuen Menüpunkt im Backend
function printshopcreatorapi_settings_menu() {
    add_menu_page( 'PSC API-Connect', 'PSC API-Connect', 'manage_options', 'printshopcreatorapi-settings', 'printshopcreatorapi_settings_page', 'dashicons-rest-api' );
}
add_action( 'admin_menu', 'printshopcreatorapi_settings_menu' );

// Erstelle Seite für API Einstellungen
function printshopcreatorapi_settings_page() {
    ?>
<style>
.psc-logo {
    position: absolute;
    right: 20px;
    top: 50%;
    margin-top: -60px;
    width: 313px;
    height: 80px;
    background: url(<?php echo plugins_url( 'images/printshop-creator.png', __FILE__ ); ?>) center top/313px 63px no-repeat;
}
.psc-version {
    position: absolute;
    width: 100%;
    bottom: 0;
    text-align: center;
    color: #72777c;
    line-height: 1em;
}
#textarea {
    width: 400px;
}
</style>

<div class="wrap">
    <a target="_blank" href="https://printshopcreator.de/"><div class="psc-logo">
			<div class="psc-version">PrintshopCreator Produkt-Konfigurator v.1.0.2</div>
		</div></a>
        <h1>PrintshopCreator Produkt-Konfigurator</h1>
        <form method="post" action="options.php" id="thisform">
            <?php
                settings_fields( 'printshopcreatorapi-settings-group' );
                do_settings_sections( 'printshopcreatorapi-settings-group' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">PSC URL</th>
                    <td><input type="text" id="api_link" name="api_link" value="<?php echo esc_attr( get_option('api_link') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Shop UUID</th>
                    <td><input type="text" id="shop_uuid" name="shop_uuid" value="<?php echo esc_attr( get_option('shop_uuid') ); ?>" /></td>
                </tr>
                <!--<tr valign="top">
                    <th scope="row">API Token</th>
                    <td><input type="text" name="api_token" value="<?php echo esc_attr( get_option('api_token') ); ?>" /></td>
                </tr>-->
                <?php if(get_option('shop_uuid') != "" AND get_option('shop_uuid') != "") { ?>
                <tr valign="top">
                    <th scope="row">Preis Ausgabe:</th>
                    <td>
                    <select name="api_price" id="api_price">
                            <option value="1" <?php if(get_option('api_price') == '1') echo 'selected'; ?>>Preis anzeigen</option>
                            <option value="1" <?php if(get_option('api_price') == '2') echo 'selected'; ?>>Wert anzeigen</option>
                            <option value="0" <?php if(get_option('api_price') == '0') echo 'selected'; ?>>Preis & Wert nicht anzeigen</option>
                    </select>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Maßeinheit</th>
                        <td><input type="text" id="shop_einheit" name="shop_einheit" value="<?php echo esc_attr( get_option('shop_einheit') ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Bestellenbutton:</th>
                    <td>
                    <select name="api_salebutton" id="api_salebutton">
                            <option value="1" <?php if(get_option('api_salebutton') == '1') echo 'selected'; ?>>Bestellenbutton anzeigen</option>
                            <option value="0" <?php if(get_option('api_salebutton') == '0') echo 'selected'; ?>>Bestellenbutton nicht anzeigen</option>
                    </select>
                    </tr>
                 <tr valign="top">
                    <th scope="row">Produktgruppe</th>
                    <td>
                        <select name="api_prgroup" id="api_prgroup"></select>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Produkte</th>
                    <td>
                        <select name="products" id="products"></select>
                    </td>
                 </tr>
                 <tr valign="top" id="product_uuid_field" style="display: none;">
                <th scope="row">Product UUID</th>
                <td><input type="text" name="product_uuid" id="product_uuid" value="" /></td>
            </tr>
            <?php } ?>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<script>
    var calcHasError = false;
    var $ = jQuery.noConflict();
    var url = document.getElementById('api_link');
    var shopUUID = document.getElementById('shop_uuid');
    var apiPrgroup = document.getElementById('api_prgroup');
    var productUuidField = document.getElementById('product_uuid_field');
    var apiPriceSelect = document.getElementById('api_price');
    var apiSaleSelect = document.getElementById('api_salebutton');
    var products = document.getElementById('products');
    var productUuid = document.getElementById('product_uuid');
    var measure = document.getElementById('shop_einheit');
    var thisform = document.getElementById('thisform');
    var selectedgroup = "<?php echo esc_attr( get_option('api_prgroup') ); ?>";
    var selectedproduct = "<?php echo esc_attr( get_option('products') ); ?>";
    var textareaField = document.getElementById('textarea');

function printshopcreator_loadProducts() {
		$('#products').empty();

		$.ajax({
			url: url.value +  "/apps/api/product/getallbyproductgroup/" + $('#api_prgroup').val(),
			contentType: "application/json",
			method: 'GET',
			success: function(result) {
				$.each(result.data, function(index, value) {
					printshopcreator_appendProduct(value);
				});

				//loadCalc();
			}
		})
	}
    function printshopcreator_appendProduct(data, depth = '') {
        if(data.uuid == selectedproduct) {
            var selectfildpr = "selected";
        }
		$('#products').append('<option value="' + data.uuid + '" ' + selectfildpr + '>' + depth + data.title + '</option>');
        setPrintshopcreatorProductUuid();
	}
function setPrintshopcreatorProductUuid() {
    productUuid.value = products.value;

const shortcodeOptions = {
    calcid: products.value,
    price: apiPriceSelect.value === '1' ? 1 : 0,
    salebtn: apiSaleSelect.value === '1' ? 1 : 0,
    measure: measure.value || ''
};

let shortcodeString = '[printshopcreatorproduct_calculator';

for (const key in shortcodeOptions) {
    if (shortcodeOptions[key]) {
        shortcodeString += ` ${key}="${shortcodeOptions[key]}"`;
    }
}
shortcodeString += ']';

productUuidField.style.display = 'table-row';
shortcode.style.display = 'block';
shortcode.innerHTML = `Bitte mit dem Folgenden Shortcode einbinden: ${shortcodeString}`;

document.getElementById('textarea').value = `${shortcodeString}`;
}
function loadProductGroups() {
		$.ajax({
			url: url.value +  "/apps/api/productgroup/gettree/" + shopUUID.value,
			contentType: "application/json",
			method: 'GET',
			success: function(result) {
				$.each(result.data, function(index, value) {
					printshopcreator_appendProductGroup(value);
				});
			}
		})
	}

	function printshopcreator_appendProductGroup(data, depth = '') {
        if(data.uuid == selectedgroup) {
            var selectfildgr = " selected";
            printshopcreator_loadProducts();
        } else {
            var selectfildgr = "";
        }
		$('#api_prgroup').append('<option value="' + data.uuid + '"' + selectfildgr + '>' + depth + data.title + '</option>');
        //
		$.each(data.children, function(index, value) {
			printshopcreator_appendProductGroup(value, depth + '>');
		})
        if(data.uuid == selectedgroup) {
            printshopcreator_loadProducts();
        }
	}
    document.addEventListener('DOMContentLoaded', function() {
document.querySelector("button").onclick = function(){
  document.querySelector("textarea").select();
  document.execCommand('copy');
};
        loadProductGroups();
        if(productUuid.value != "") {
            productUuidField.style.display = 'table-row';
            shortcode.style.display = 'block';
            const shortcodeOptions = {
    calcid: products.value,
    price: apiPriceSelect.value === '1' ? 1 : 0,
    salebtn: apiSaleSelect.value === '1' ? 1 : 0,
    measure: measure.value || ''
};

let shortcodeString = '[printshopcreatorproduct_calculator';

for (const key in shortcodeOptions) {
    if (shortcodeOptions[key]) {
        shortcodeString += ` ${key}="${shortcodeOptions[key]}"`;
    }
}
shortcodeString += ']';

productUuidField.style.display = 'table-row';
shortcode.style.display = 'block';
shortcode.innerHTML = `Bitte mit dem Folgenden Shortcode einbinden: ${shortcodeString}`;

document.getElementById('textarea').value = `${shortcodeString}`;
        }
apiPrgroup.addEventListener('change', function() {
printshopcreator_loadProducts();
    });
products.addEventListener('change', function() {
setPrintshopcreatorProductUuid();
    });
thisform.addEventListener('change', function() {
    const shortcodeOptions = {
    calcid: products.value,
    price: apiPriceSelect.value === '1' ? 1 : 0,
    salebtn: apiSaleSelect.value === '1' ? 1 : 0,
    measure: measure.value || ''
};

let shortcodeString = '[printshopcreatorproduct_calculator';

for (const key in shortcodeOptions) {
    if (shortcodeOptions[key]) {
        shortcodeString += ` ${key}="${shortcodeOptions[key]}"`;
    }
}
shortcodeString += ']';

productUuidField.style.display = 'table-row';
shortcode.style.display = 'block';
shortcode.innerHTML = `Bitte mit dem Folgenden Shortcode einbinden: ${shortcodeString}`;

document.getElementById('textarea').value = `${shortcodeString}`;
});
measure.addEventListener('change', function() {
if(apiSaleSelect.value == '1' && apiPriceSelect.value == "1" && measure.value != "") {
}
    });
    });
function copyToClipBoard() {

var content = document.getElementById('textarea');

content.select();
document.execCommand('copy');

msg.innerHTML = "Text in die Zwischenablage kopiert.";
}
</script>
<div id="shortcode" style="display: none;"></div>
<br/>
<div id="msg"></div>
<textarea id="textarea"></textarea><br />
<button onclick="copyToClipBoard()">Shortcode in Zwischenablage kopieren</button>
<?php
}

// Registriere Einstellungen
function printshopcreatorapi_settings_init() {
    register_setting( 'printshopcreatorapi-settings-group', 'api_link' );
    register_setting( 'printshopcreatorapi-settings-group', 'shop_uuid' );
    register_setting( 'printshopcreatorapi-settings-group', 'api_token' );
    register_setting( 'printshopcreatorapi-settings-group', 'api_price' );
    register_setting( 'printshopcreatorapi-settings-group', 'shop_einheit' );
    register_setting( 'printshopcreatorapi-settings-group', 'api_salebutton' );
    register_setting( 'printshopcreatorapi-settings-group', 'api_prgroup' );
    register_setting( 'printshopcreatorapi-settings-group', 'product_uuid' );
    register_setting( 'printshopcreatorapi-settings-group', 'products' );
}
add_action( 'admin_init', 'printshopcreatorapi_settings_init' );

function printshopcreatorapi_settings_link($links) { 
    $settings_link = '<a href="admin.php?page=printshopcreatorapi-settings">Einstellungen</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
  }
  add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'printshopcreatorapi_settings_link');