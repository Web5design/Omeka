<?php
/**
 * Uses url_for() to generate <a> tags for a given link
 *
 * @param Omeka_Record|string $record The name of the controller to use for the
 * link.  If a record instance is passed, then it inflects the name of the 
 * controller from the record class.
 * @param string $action The action to use for the link (optional)
 * @param string $text The text to put in the link
 * @param array $props Attributes for the <a> tag
 * @return string HTML
 **/
function link_to($record, $action=null, $text, $props = array())
{
    $urlOptions = array();
    //Use Zend Framework's built-in 'default' route
    $route = 'default';
    
    if($record instanceof Omeka_Record) {
        $urlOptions['controller'] = strtolower(Inflector::pluralize(get_class($record)));
        $urlOptions['id'] = $record->id;
        $route = 'id';
    }
    else {
        $urlOptions['controller'] = (string) $record;
    }
    
    if($action) $urlOptions['action'] = (string) $action;
    
	$url = url_for($urlOptions, $route);

	$attr = !empty($props) ? ' ' . _tag_attributes($props) : '';
	return '<a href="'. $url . '"' . $attr . ' title="View '. htmlentities($text).'">' . h($text) . '</a>';
}

function link_to_item($item, $action='show', $text=null, $props=array())
{
	$text = (!empty($text) ? $text : (!empty($item->title) ? $item->title : '[Untitled]'));
	
	return link_to($item, $action, $text, $props);
}

function link_to_items_rss($params=array())
{	
	return '<a href="' . items_rss_uri($params) . '" class="rss">RSS</a>';
}

/**
 * 
 *
 * @return string
 **/
function link_to_next_item($item, $text="Next Item -->", $props=array())
{
	if($next = $item->next()) {
		return link_to($next, 'show', $text, $props);
	}
}

/**
 * 
 *
 * @return string
 **/
function link_to_previous_item($item, $text="<-- Previous Item", $props=array())
{
	if($previous = $item->previous()) {
		return link_to($previous, 'show', $text, $props);
	}
}

/**
 * 
 *
 * @return string
 **/
function link_to_collection($collection, $action='show', $text=null, $props=array())
{
	$text = (!empty($text) ? $text : (!empty($collection->name) ? $collection->name : '[Untitled]'));
	
	return link_to($collection, $action, $text, $props);
}

/**
 * 
 *
 * @return string|false
 **/
function link_to_thumbnail($item, $props=array(), $action='show', $random=false)
{
    return _link_to_archive_image($item, $props, $action, $random, 'thumbnail');
}

/**
 *
 * @return string|false
 **/
function link_to_fullsize($item, $props=array(), $action='show', $random=false)
{
    return _link_to_archive_image($item, $props, $action, $random, 'fullsize');
}

/**
 * 
 *
 * @return string|false
 **/
function link_to_square_thumbnail($item, $props=array(), $action='show', $random=false)
{
    return _link_to_archive_image($item, $props, $action, $random, 'square_thumbnail');
}

/**
 * Returns a link to an item, where the link has been populated by a specific image format for the item
 *
 * @return string|false
 **/
function _link_to_archive_image($item, $props=array(), $action='show', $random=false, $imageType = 'thumbnail')
{
	if(!$item or !$item->exists()) return false;
	
	$path = 'items/'.$action.'/' . $item->id;
	$output = '<a href="'. uri($path) . '" ' . _tag_attributes($props) . '>';
	
	if($random) {
		$output .= archive_image($item, array(), null, null, $imageType);
	}else {
		$output .= archive_image($item->Files[0], array(), null, null, $imageType);
	}
	$output .= '</a>';	
	
	return $output;
}

/**
 * 
 *
 * @return string
 **/
function link_to_home_page($text, $props = array())
{
	$uri = WEB_ROOT;
	return '<a href="'.$uri.'" '._tag_attributes($props).'>'.h($text)."</a>\n";
}

/**
 * 
 *
 * @return string
 **/
function link_to_admin_home_page($text, $props = array())
{
	return '<a href="'.admin_uri().'" '._tag_attributes($props).'>'.h($text)."</a>\n";
}

/**
 *	The pagination function from the old version of the software
 *  It looks more complicated than it might need to be, but its also more flexible.  We may decide to simplify it later
 */
function pagination_links( $num_links = 5, $menu = null, $page = null, $per_page = null, $total_results=null, $link=null, $page_query = null )
{
	
	//If no args passed, retrieve the stored 'pagination' value
	if(Zend_Registry::isRegistered('pagination')) {
		$p = Zend_Registry::get('pagination');
	}
	
	if(empty($per_page)) {
		$per_page = $p['per_page'];
	} 
	if(empty($num_links)) {
		$num_links = $p['num_links'];
	}
	if(empty($total_results)) {
		$total_results = $p['total_results'];
	}
	if(empty($page)) {
		$page = $p['page'];
	}
	if(empty($link)) {
		$link = $p['link'];
	}

	//Avoid division by zero error
	if(!$per_page) return;

		$num_pages = ceil( $total_results / $per_page );
		$num_links = ($num_links > $num_pages) ? $num_pages : $num_links;
				
		$query = !empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : null;
		
		if ( $page_query )
		{
			//Using the power of regexp we replace only part of the query string related to the pagination
			if( preg_match( '/[\?&]'.$page_query.'/', $query ) ) 
			{
				$p = '/([\?&])('.preg_quote($page_query) . ')=([0-9]*)/';
				$pattern = preg_replace( $p, '$1$2='.preg_quote('%PAGE%'), $query );
			}
			else $pattern = ( !empty($query) )  ? $query . '&' . $page_query . '=' . '%PAGE%' : '?' . $page_query . '=' . '%PAGE%' ; 
	
		}
		else
		{
			$pattern = '%PAGE%' . $query;
		}

		//We don't have enough for pagination
		if($total_results < $per_page) {
			$html = '';
		} else {
			
		if( $page > 1 ) {
			$html = '<ul><li class="first"><a href="' . $link . str_replace('%PAGE%', 1, $pattern) . '">First</a></li><li class="previous"><a href="' . $link . str_replace('%PAGE%', ($page - 1), $pattern) . '">Previous</a></li>';
		} elseif( $page == 1) {
			$html = '<ul>';
		}

		$buffer = floor( ( $num_links - 1 ) / 2 );
		$start_link = ( ($page - $buffer) > 0 ) ? ($page - $buffer) : 1;
		$end_link = ( ($page + $buffer) < $num_pages ) ? ($page + $buffer) : $num_pages;

		if( $start_link == 1 ) {
			$end_link += ( $num_links - $end_link );
		}elseif( $end_link == $num_pages ) {
			$start_link -= ( $num_links - ($end_link - $start_link ) - 1 );
		}

		for( $i = $start_link; $i < $end_link+1; $i++) {
			if( $i <= $num_pages ) {
				if( $page == $i ) {
					$html .= '<li class="current">' . $i . '</li>';
				} else {
					$html .= '<li><a href="' . $link . str_replace('%PAGE%', $i, $pattern) . '">' . ($i) . '</a></li>';
				}
			}
		}

		if( $page < $num_pages ) {
			$html .= '<li class="next"><a href="' . $link . str_replace('%PAGE%', ($page + 1), $pattern). '">Next</a></li><li class="last"><a href="' . $link . str_replace('%PAGE%', ($num_pages), $pattern) . '">Last</a></li>';
		}

		$html .= '</ul>';
			
		if ($menu) {
			$html .= '<select class="pagination-link" onchange="location.href = \''.$link . $page . '?per_page=' . ('\' + this.value + \'') .'\'">';
			$html .= '<option>Results Per Page:&nbsp;</option>';
			$per_page_limits = array(10, 25, 50);
			foreach ($per_page_limits as $per_page_limit) {
				$html .= '<option value="' . $per_page_limit . '"';
				$html .= '>' . $per_page_limit . ' results' . '</option>';
			}
			$html .= '</select>';
		}
		}
		return $html;		
	}

