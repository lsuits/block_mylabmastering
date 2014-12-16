<?php

/**
 * Pearson MyLab & Mastering block code.
 *
 * @package    block_mylabmastering
 * @copyright  2012-2013 Pearson Education
 * @license    
 */
defined('MOODLE_INTERNAL') || die;
require_login();

class block_mylabmastering extends block_base {
    public function init() {
        $this->title = get_string('mylabmastering', 'block_mylabmastering');
    }
    
    public function get_content() {
	    global $CFG, $COURSE, $USER, $PAGE, $DB;
	    require_once($CFG->dirroot.'/blocks/mylabmastering/locallib.php');
	    require_once($CFG->dirroot.'/lib/modinfolib.php');
	   	$this->content = new stdClass;
	   	$this->content->text = '';
	   	$this->content->footer = '';
	   	$strHTML = '';
	   	
	   	// check capabilities and throw error if needed
	   	has_capability('block/mylabmastering:view', context_course::instance($COURSE->id));
	   	 
	   	
	   	if (mylabmastering_is_student($COURSE->id)) {
	   		return $this->content;
	   	}	   	
    		 
	    if (mylabmastering_is_global_configured()) {
	    	// this means that the base url, key and secret are configured correctly
	    	// check for course mapping
	    	$mm_local_config = mylabmastering_course_has_config($COURSE->id);
	    	if ($mm_local_config) {
	    		$local_code = $mm_local_config->code;
	    		$mapping = mylabmastering_get_mapping($COURSE->id);
	    		
	    		if ($local_code === 'unmapped') {
	    			if ($mapping->code !== 'unmapped') {
	    				// the course has been created on the MM side
	    				$product_content = mylabmastering_get_content_links($mapping->code);
	    				
	    				if ($product_content) {
	    					$links = $product_content->bundle->links;
	    					foreach ($links as $link) {
	    						mylabmastering_create_lti_type($link,$COURSE->id,$USER->id);
	    					}	    
	    					$mm_local_config->code = $mapping->code;
	    					
	    					if (!isset($product_content->platform) || trim($product_content->platform) == '') {
	    						$mm_local_config->platform = 'Default';
	    					}
	    					else {
	    						$mm_local_config->platform = $product_content->platform;
	    					}
	    					
	    					if (!isset($product_content->description) || trim($product_content->description) == '') {
	    							$mm_local_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: Default</p>', FORMAT_HTML);
    						}
    						else {
    							$mm_local_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: '.$product_content->platform.'</p>', FORMAT_HTML);
    						}
	    					mylabmastering_update_course_config($mm_local_config);	    				
	    				}
	    			}
	    		}
	    		else {
	    			if ($local_code === $mapping->code) {
	    				// no change in mapping but need to check the bundle
	    				if ($mm_local_config->platform !== $mapping->platform) {
	    					// new link bundle for code
	    					$product_content = mylabmastering_get_content_links($mapping->code);
	    					 
	    					if ($product_content) {
	    						$links = $product_content->bundle->links;
	    						foreach ($links as $link) {
	    							mylabmastering_create_lti_type($link,$COURSE->id,$USER->id);
	    						}
	    						$mm_local_config->code = $mapping->code;

	    						if (!isset($product_content->platform) || trim($product_content->platform) == '') {
	    							$mm_local_config->platform = 'Default';
	    						}
	    						else {
	    							$mm_local_config->platform = $product_content->platform;
	    						}	    							
	    						
	    						if (!isset($product_content->description) || trim($product_content->description) == '') {
	    							$mm_local_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: Default</p>', FORMAT_HTML);
	    						}
	    						else {
	    							$mm_local_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: '.$product_content->platform.'</p>', FORMAT_HTML);
	    						}
	    						mylabmastering_update_course_config($mm_local_config);
	    					}
	    				}
	    			}
	    			else {
	    				// mapping changed
	    				if ($mapping->code === 'unmapped') {
	    					// course has been unmapped
	    					// revert original state
	    					mylabmastering_handle_code_change($COURSE->id);
	    					$mm_local_config->code = 'unmapped';
	    					$mm_local_config->platform = '';
	    					$mm_local_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: None</p>', FORMAT_HTML);
	    					$mm_local_config->description .= format_text('<p>Use the Pearson MyLab & Mastering Tools link to get started.</p>', FORMAT_HTML);
	    					mylabmastering_update_course_config($mm_local_config);	    					
	    				}
	    				else {
	    					// mapping changed to a new product
	    					mylabmastering_handle_code_change($COURSE->id);
	    					$product_content = mylabmastering_get_content_links($mapping->code);
	    						
	    					if ($product_content) {
	    						$links = $product_content->bundle->links;
	    						foreach ($links as $link) {
	    							mylabmastering_create_lti_type($link,$COURSE->id,$USER->id);
	    						}
	    						$mm_local_config->code = $mapping->code;
	    						if (!isset($product_content->platform) || trim($product_content->platform) == '') {
	    							$mm_local_config->platform = 'Default';
	    						}
	    						else {
	    							$mm_local_config->platform = $product_content->platform;
	    						}
	    							
	    						if (!isset($product_content->description) || trim($product_content->description) == '') {
	    							$mm_local_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: Default</p>', FORMAT_HTML);
	    						}
	    						else {
	    							$mm_local_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: '.$product_content->platform.'</p>', FORMAT_HTML);
	    						}
	    							    								    							    						
	    						mylabmastering_update_course_config($mm_local_config);
	    					}	    					
	    				}
	    				rebuild_course_cache($COURSE->id);
	    				redirect($this->page->url);	    				 
	    			}
	    		}
	    	}
	    	else {
	    		// initial install
	    		$mm_tools_id = mylabmastering_create_highlander_link('mm_tools', $CFG->mylabmastering_url.'/highlander/api/o/lti/tools', 'MyLab & Mastering Tools');
	    		
	    		$mm_config = new stdClass;
	    		$mm_config->course = $COURSE->id;
	    		$mm_config->code = 'unmapped';
	    		$mm_config->platform = '';
	    		$mm_config->description = format_text('<p>Pearson MyLab & Mastering course pairing: None</p>', FORMAT_HTML);
	    		$mm_config->description .=  format_text('<p>Use the <a href="'.$CFG->wwwroot."/mod/lti/view.php?l=".$mm_tools_id.'" >Pearson MyLab & Mastering Tools link</a> to get started.</p>', FORMAT_HTML);
	    		mylabmastering_create_course_config($mm_config);	    		 
	    		
	    		rebuild_course_cache($COURSE->id);
	    		redirect($this->page->url);	    		 
	    	}    	
	    	
	    	$strHTML .= '<div id="'.'block_mylabmastering_tree'.'" >';
	    	$strHTML .= $mm_local_config->description;	    	
	    	$strHTML .= '<br/>';
	    	$strHTML .= '</div>';
	    	
	    }
	    else {
	    	// this means that the base url, key and secret are not configured correctly
	    	$strHTML .= get_string('mylabmastering_notconfigured', 'block_mylabmastering');
	    }	    	   
	    
	    $this->content->text = format_text($strHTML, FORMAT_HTML);
	    return $this->content;
	}
  
  public function specialization() {
	  if (!empty($this->config->title)) {
	    $this->title = $this->config->title;
	  } else {
	    $this->title = get_string('mylabmastering', 'block_mylabmastering');
	  }
  }
  
  public function applicable_formats() {
	  return array('course-view' => true);
  }
}   // Here's the closing bracket for the class definition
