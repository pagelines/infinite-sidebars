<?php
/*
Plugin Name: KI Infinite Sidebars
Author: Kether Interactive
Author URI: http://infinitesidebars.kether-interactive.ca
Version: 1.0
Description: Plugin to allow users to create an infinite amount of sidebars
Class Name: kiInfiniteSidebars
*/

// Let's make the magick happen
add_action('pagelines_setup', 'ki_plugin_init' );
function ki_plugin_init() {
	if( !function_exists('pl_has_editor') )
		return;

	$ki_is = new kiInfiniteSidebars;
}

class kiInfiniteSidebars {
    
	const version = '1.0';

	function __construct() {
		$this->name 	= 'Infinite Sidebars';
		$this->id   	= 'ki-'.str_replace(' ', '-',$this->name);
        $this->dir  	= plugin_dir_path( __FILE__ );
        $this->url  	= plugins_url( '', __FILE__ );
        $this->sidebars = $this->ki_get_sidebars();

		add_action( 'template_redirect',  array(&$this,'ki_less' ));
		add_action( 'init', array( &$this, 'ki_init' ) );

	    add_action( 'wp_ajax_ki_ajax_add_sidebar', array(&$this,'ki_ajax_add_sidebar') );
	    add_action( 'wp_ajax_ki_ajax_remove_sidebar', array(&$this,'ki_ajax_remove_sidebar') );
    }

    // Initialization
	function ki_init(){
		add_filter( 'pl_settings_array', array(&$this, 'ki_options'));

		if( is_array( $this->ki_get_sidebars() ) ){
            foreach($this->ki_get_sidebars() as $sidebar){
                $sidebar_class = $this->ki_class_name($sidebar);
                register_sidebar(array(
                    'name'          => $sidebar,
                    'before_widget' => '<aside class="widget side-'. $sidebar_class .' %2$s">',
                    'after_widget'  => '</aside><div class="clear"></div>',
                    'before_title'  => '<h3 class="widget-title">',
                    'after_title'   => '</h3>',
                ));
            }
        } 
	}

	// Include our LESS
	function ki_less() {
        $file = sprintf( '%sstyle.less', plugin_dir_path( __FILE__ ) );
        if(function_exists('pagelines_insert_core_less')) {
            pagelines_insert_core_less( $file );
        }
    }

    // Create Infinite Sidebars panel in Global Options
    function ki_options( $settings ){

        $settings[ $this->id ] = array(
                'name'  => $this->name,
                'icon'  => 'icon-columns',
                'pos'   => 5,
                'opts'  => $this->ki_global_opts()
        );

        return $settings;
    }

    // Panel Options
    function ki_global_opts(){

    	$options = array();
    
        $options[] = array(
			'type' 		=> 'template',
			'key'       => 'ki_sidebar_add',
			'span'		=> 3,
            'title' 	=> __("Add New Sidebar", $this->id ),
            'template' 	=> $this->ki_add_sidebar_form(),
        );

        $options[] = array(
			'type' 		=> 'template',
			'span'		=> 3,
			'key'       => 'ki_tpl_sidebar_display',
            'title' 	=> __("Existing Sidebars", $this->id ),
            'template' 	=> $this->sidebar_display(),
        );

        return array_merge($options);
    }

    // Form to Add a Sidebar
    function ki_add_sidebar_form(){
    	ob_start();
    	?>
    	<div class="ki_sidebar_intro">
	    	<script>
	    		!function ($) {
	    			$saving = $(".form-save-sidebar .saving");
	    			$saving.hide();
	    			$('.removal_status').each(function(){
	    				$(this).hide();
	    			});
	    			var ajaxurl = '<?php echo get_bloginfo("url"); ?>/wp-admin/admin-ajax.php';
	    			$('#sidebar-name').keypress(function(e) {
				        if (e.keyCode == 13) {
	    					e.preventDefault();
				            $saving.fadeIn();
							data = { action: 'ki_ajax_add_sidebar', sidebarName: $("#sidebar-name").val() };
							$.post(ajaxurl, data, function(response){
								$("#sidebar-name").val('');
								$saving
									.html('<b>Success!</b>')
									.css('color', 'green')
									.delay(400)
									.queue(function() {
								        $(this).html('<b>Refreshing Page...</b>').css('color', 'orange');
								    })
								    .delay(300)
								    .fadeOut();
							    console.log(response);
		    					location.reload();
							});
				        }
				    });
			    	$(".save-sidebar").on("click", function(e) {
			    		$sidebarName = $("#sidebar-name").val();
			    		if ($sidebarName != ''){
		    				$saving.fadeIn();
							data = { action: 'ki_ajax_add_sidebar', sidebarName: $sidebarName };
							$.post(ajaxurl, data, function(response){
								$("#sidebar-name").val('');
								$saving
									.html('<b>Success!</b>')
									.css('color', 'green')
									.delay(400)
									.queue(function() {
								        $(this).html('<b>Refreshing Page...</b>').css('color', 'orange');
								    })
								    .delay(300)
								    .fadeOut();
							    console.log(response);
		    					location.reload();
							});
						}
					});
					$("#ki_sidebars_table").on("click", function(e) {
						$target = $( $(e.target).parent() );
						confirmation = confirm('Are you sure you want to remove "' + $target.data("remove-sidebar") + '"?\nThis will remove any widgets you have assigned to this sidebar.');
		                if(confirmation){
							$target.children('.removal_status').fadeIn();
				    		data = { action: 'ki_ajax_remove_sidebar', sidebarName: $target.data("remove-sidebar") };
				    		$.post(ajaxurl, data, function(response){
								$target
									.children('.removal_status')
									.html('<b>Done!</b>')
									.css('color', 'green')
									.delay(400)
									.queue(function() {
								        $(this).html('<b>Refreshing...</b>').css('color', 'orange');
								    })
								    .delay(300)
								    .fadeOut();
							    console.log(response);
		    					location.reload();
							});
						} else{
		                    return false;
		                }
					});
				}(window.jQuery);
			</script>
	        <div class="form-save-sidebar">
				<fieldset>
					<label for="template-name">Add New Sidebar (required)</label>
					<input type="text" id="sidebar-name" name="sidebar-name" required />
					<div class="saving_container">
						<div class="save-sidebar btn btn-success btn-small">Save New Sidebar</div>
						<div class="saving"><i class="icon-spinner icon-spin"></i> <span class="saving_text">Saving...</span></div>
					</div>
				</fieldset>
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
    
    // Show all currently existing sidebars
    function sidebar_display(){

		ob_start();
		$sidebars = $this->ki_get_sidebars();
		$alt = 0;
		if( is_array($this->sidebars) && !empty($this->sidebars) ):
		?>
			<h6><?php echo $this->dump; ?></h6>
            <table id="ki_sidebars_table" style="width:100%;">
                <tr>
                    <th>NAME</th>
                    <th>CSS CLASS</th>
                    <th>SHORTCODE</th>
                    <th>REMOVE</th>
                </tr>
                <?php foreach( $this->sidebars as $sidebar ): 
                	$alt_class = ($alt%2 == 0 ? 'alternate' : ''); ?>
				<tr class="<?php echo $alt_class; ?>">
                    <td style="padding-top: 10px; padding-bottom: 10px;font-weight: 700;"><?php echo $sidebar; ?></td>
                    <td style="padding-top: 10px; padding-bottom: 10px;"><code style="padding: 4px 7px; border: 1px solid #bbb;">side-<?php echo $this->ki_class_name($sidebar); ?></code></td>
                    <td style="padding-top: 10px; padding-bottom: 10px;"><code style="padding: 4px 7px; border: 1px solid #bbb;">[ki_sidebar name="<?php echo $sidebar; ?>"]</code></td>
                    <td style="padding-top: 10px; padding-bottom: 10px;">
                    	<a class="remove_container" data-remove-sidebar="<?php echo $sidebar; ?>">
                    		<div class="remove_btn btn btn-important btn-small">Remove</div>
                    		<div class="removal_status"><i class="icon-spinner icon-spin"></i> <span class="removing_text">Removing...</span></div>
						</a>
                    </td>
                </tr>
                <?php $alt++;
                endforeach; ?>
            </table>
		<?php else: ?>
			<div class="alert alert-warning alert-block"><h5 class="alert-heading zmt">No Sidebars</h5>There currently aren't any sidebars created by Infinite Sidebars, but this message will disappear as soon as you do.</div>
		<?php 
		endif;
		return ob_get_clean();
	}
	
	// Convert sidebar name to a css class 
    function ki_class_name($name){
        $class = str_replace(' ','_',$name);
        $class = str_replace( array(' ',',','.','"',"'",'/',"\\",'+','=',')','(','*','&','^','%','$','#','@','!','~','`','<','>','?','[',']','{','}','|',':',),'',$class);
        return strtolower($class);
    }

    // Update Sidebars
    function ki_update_sidebars($sidebar_array){
        pl_setting_update('ki_sidebars_array', serialize($sidebar_array) );
    }  

    // Retrieve Sidebars
    function ki_get_sidebars(){
        $sidebars = pl_setting('ki_sidebars_array');
        return unserialize( $sidebars );
    }
	
	// Add Sidebar to our Sidebar Array
	function ki_ajax_add_sidebar(){
		$sidebars 			= $this->ki_get_sidebars();
		$sidebars[]			= $_POST['sidebarName'];
		$this->ki_update_sidebars($sidebars);
		wp_send_json_success($_POST['sidebarName'].' Sidebar Added');
	}

	// Remove Sidebar from our Sidebar Array
	function ki_ajax_remove_sidebar(){
		$sidebars = $this->ki_get_sidebars();
		$removing = $_POST['sidebarName'];

		$key = array_search($removing, $sidebars);
		unset($sidebars[$key]);
		$this->ki_update_sidebars($sidebars);

    	wp_send_json_success('Sidebar: '.$removing.' was removed.'); // encode and send response

	}
}

function ki_dynamic_sidebar($name='Primary Sidebar'){
    if(function_exists('dynamic_sidebar') && dynamic_sidebar($name)) : 
    endif;
    return true;
}

function ki_sidebar_shortcode( $atts ) {
    extract( shortcode_atts( array(
        'name' => 'Primary Sidebar',
    ), $atts ) );

    if(function_exists('dynamic_sidebar') && dynamic_sidebar($name)) : 
    endif;
}
add_shortcode( 'ki_sidebar', 'ki_sidebar_shortcode' );
