<?php

add_filter( 'pre_docs_to_wp_strip', 'dtwp_extract_styles', 10, 1 );
function dtwp_extract_styles( $contents ) {

		//PHP doesn't honor lazy matches very well, apparently, so add newlines
		$contents[ 'contents' ] = str_replace( '}', "}\r\n", $contents[ 'contents' ] );


		preg_match_all( '#.c(?P<digit>\d+){(.*?)font-weight:bold(.*?)}#', $contents[ 'contents' ], $boldmatches );
		preg_match_all('#.c(?P<digit>\d+){(.*?)font-style:italic(.*?)}#', $contents[ 'contents' ], $italicmatches);
		
		if( !empty( $boldmatches[ 'digit' ] ) ) {
		
			foreach( $boldmatches[ 'digit' ] as $boldclass ) {
				$contents[ 'contents' ] = preg_replace( '#<span class="(.*?)c' . $boldclass . '(.*?)">(.*?)</span>#s', '<span class="$1c' . $boldclass . '$2"><strong>$3</strong></span>', $contents[ 'contents' ] );
			}
		
		}
		
		if( !empty( $italicmatches[ 'digit' ] ) ) {
		
			foreach( $italicmatches[ 'digit' ] as $italicclass ) {
				$contents[ 'contents' ] = preg_replace( '#<span class="(.*?)c' . $italicclass . '(.*?)">(.*?)</span>#s', '<span class="$1c' . $italicclass . '$2"><em>$3</em>', $contents[ 'contents' ] );
			}
		
		}
		
		return $contents;

}


add_filter( 'pre_docs_to_wp_insert', 'dtwp_clean_content_filter', 10, 1 );
function dtwp_clean_content_filter( $post_array ) {	
	$original_content = $post_array[ 'post_content' ];
	$cleaned_content = dtwp_clean_content( $original_content );
	$post_array[ 'post_content' ] = $cleaned_content[ 'content' ];
	return $post_array;

}


function dtwp_clean_content($post_content) {
		$post_content = str_replace( array( "\r\n", "\n\n", "\r\r", "\n\r" ), "\n", $post_content );
		$post_content = preg_replace('/<div(.*?)>/', '<div>', $post_content);
		$post_content = preg_replace('/<p(.*?)>/', '<p>', $post_content);
		
		//Match all the comments into an array. We're doing this before anything else because the </div> is importqnt
		//preg_match_all( '/<div><p><a href="#cmnt_ref[\d]">\[[\w]\]<\/a>(.*?)<\/div>/', $post_content, $comments, PREG_PATTERN_ORDER);
		//$comments = implode( "\r\n\r\n", $comments[1] );
		
		//Take out the comments
		//$post_content = preg_replace( '/<div><p><a href="#cmnt_ref(.*?)<\/div>/', '', $post_content );
		//Take out the comment refers
		//$post_content = preg_replace( '/<a href="#cmnt(.*?)<\/a>/', '', $post_content );
		
		$post_content = str_replace( '<div>','<p>',$post_content );
		$post_content = str_replace( '</div>', '</p>',$post_content );
		//adding <img> to keep the images info
		$post_content = strip_tags($post_content, '<strong><b><i><em><a><u><br><p><ol><ul><li><h1><h2><h3><h4><h5><h6><img>' );
		$post_content = str_replace( '--','&mdash;',$post_content );
		$post_content = str_replace( '<br><br>','<p>',$post_content );
		$post_content = str_replace( '<br>&nbsp;&nbsp;&nbsp;', '\n\n', $post_content );
		$post_content = str_replace( '<br>&nbsp;&nbsp;&nbsp;','\n\n',$post_content);
		$post_content = str_replace( '<br><br>', '\n\n', $post_content );
		$post_content = trim( $post_content );
		$pees = explode( '<p>', $post_content );
		$trimmed = array();
		foreach( $pees as $p )
			$trimmed[] = trim( $p );
		$post_content = implode( '<p>', $trimmed );
		//atumatically adding <pre> and </pre> for codes in backquotes
		$post_content = str_replace('<p>`', '<p><pre>`', $post_content);
        $post_content = str_replace('`</p>', '`</p><pre>', $post_content);
		$post_content = preg_replace( "/<p><\/p>/", '', $post_content );
		
		//return array( 'content' => $post_content, 'comments' => $comments );
		return array( 'content' => $post_content);
}