<?php
/*******************************************************************************
 * Function:        STP_Auto_PostTags
 * Purpose:                Display a tag list after the post content
 * Input:                $content
 * Output:                $content+STP_GetRelatedTags: String
 ******************************************************************************/
function STP_Auto_PostTags($content) {
	return $content.STP_GetPostTags(null, null, null, null, true);
}

/*******************************************************************************
* Function:	STP_PostTags / STP_GetPostTags
* Purpose:		Outputs the list of tags related to the current post.
* 				Use this function in the "Loop".
* Input:		...
* Output:		STP_PostTags: Echo
* 				STP_GetPostTags: String
******************************************************************************/
function STP_GetPostTags($linkformat=null, $include_cats=null, $tagseparator=null, $notagstext=null, $include_content=null, $include_content_before=null, $include_content_after=null) {
	global $STagging;
	return $STagging->outputPostTags($linkformat, $include_cats, $tagseparator, $notagstext, $include_content, $include_content_before, $include_content_after);
}
function STP_PostTags($linkformat=null, $include_cats=null, $tagseparator=null, $notagstext=null, $include_content=null, $include_content_before=null, $include_content_after=null) {
	echo STP_GetPostTags($linkformat, $include_cats, $tagseparator, $notagstext, $include_content, $include_content_before, $include_content_after);
}

/*******************************************************************************
* Function:	STP_Tagcloud
* Purpose:		Displays a tagcloud
* Input:		...
* Output:		STP_Tagcloud: Echo
* 				STP_GetTagcloud: String
******************************************************************************/
function STP_GetTagcloud($linkformat=null, $tagseparator=null, $include_cats=null, $sort_order=null, $display_max=null, $display_min=null, $scale_max=null, $scale_min=null, $notagstext=null, $max_color=null, $min_color=null, $max_size=null, $min_size=null, $unit=null, $limit_days=null, $limit_cat=null, $exclude_cat=null) {
	global $STagging;
	return $STagging->createTagcloud($linkformat, $tagseparator, $include_cats, $sort_order, $display_max, $display_min, $scale_max, $scale_min, $notagstext, $max_color, $min_color, $max_size, $min_size, $unit, $limit_days, $limit_cat, $exclude_cat);
}
function STP_Tagcloud($linkformat=null, $tagseparator=null, $include_cats=null, $sort_order=null, $display_max=null, $display_min=null, $scale_max=null, $scale_min=null, $notagstext=null, $max_color=null, $min_color=null, $max_size=null, $min_size=null, $unit=null, $limit_days=null, $limit_cat=null, $exclude_cat=null) {
	echo STP_GetTagcloud($linkformat, $tagseparator, $include_cats, $sort_order, $display_max, $display_min, $scale_max, $scale_min, $notagstext, $max_color, $min_color, $max_size, $min_size, $unit, $limit_days, $limit_cat, $exclude_cat);
}

/*******************************************************************************
* Function:     STP_GetTagcloud_ByCategory / STP_Tagcloud_ByCategory
* Purpose:		Displays a tagcloud for a specifik category
* Input:		...
* Output:		STP_Tagcloud_ByCategory: Echo
* 				STP_GetTagcloud_ByCategory: String
******************************************************************************/
function STP_Tagcloud_ByCategory( $limit_cat ) {
	echo STP_GetTagcloud_ByCategory( $limit_cat );
}
function STP_GetTagcloud_ByCategory( $limit_cat ) {
	$limit_cat = (int) $limit_cat;
	return STP_GetTagcloud($linkformat, $tagseparator, $include_cats, $sort_order, $display_max, $display_min, $scale_max, $scale_min, $notagstext, $max_color, $min_color, $max_size, $min_size, $unit, $limit_days, $limit_cat, $exclude_cat);
}

/*******************************************************************************
* Function:	STP_RelatedPosts
* Purpose:		Presents related posts according to the tags of the current post.
* Input:		...
* Output:		STP_RelatedPosts: Echo
* 				STP_GetRelatedPosts: String
******************************************************************************/
function STP_GetRelatedPosts($format=null, $postsseparator=null, $sortorder=null, $limit_qty=null, $limit_days=null, $dateformat=null, $nothingfound=null, $includepages=null, $post_id=null, $excludecat=null, $excludetag=null) {
	global $STagging;
	return $STagging->createRelatedPostsList($format, $postsseparator, $sortorder, $limit_qty, $limit_days, $dateformat, $nothingfound, $includepages, $post_id, $excludecat, $excludetag);
}
function STP_RelatedPosts($format=null, $postsseparator=null, $sortorder=null, $limit_qty=null, $limit_days=null, $dateformat=null, $nothingfound=null, $includepages=null, $post_id=null, $excludecat=null, $excludetag=null) {
	echo STP_GetRelatedPosts($format, $postsseparator, $sortorder, $limit_qty, $limit_days, $dateformat, $nothingfound, $includepages, $post_id, $excludecat, $excludetag);
}


function STP_getRelatedPostsForPost( $post_id ) {
	$post_id = (int) $post_id;
	return st_get_related_posts('post_id='.$post_id);
}
function STP_RelatedPostsForPost( $post_id ) {
	echo STP_getRelatedPostsForPost( $post_id );
}

function STP_GetRelatedTags($format=null, $tagseparator=null, $sortorder=null, $nonfoundtext=null, $limit_qty=null) {
	return '';
}
function STP_RelatedTags($format=null, $tagseparator=null, $sortorder=null, $nonfoundtext=null, $limit_qty=null) {
	return '';
}

function STP_GetRelatedTagsRemoveTags($format=null, $separator=null, $nonfoundtext=null) {
	return '';
}
function STP_RelatedTagsRemoveTags($format=null, $separator=null, $nonfoundtext=null) {
	return '';
}

function STP_GetMetaKeywords($before='', $after='', $separator=',', $include_cats=null) {
	return st_get_meta_keywords();
}
function STP_MetaKeywords($before='', $after='', $separator=',', $include_cats=null) {
	st_meta_keywords();
}

function STP_IsTagView() {
	return is_tag();
}

function STP_GetCurrentTagSet($separator=', ') {
	return single_tag_title('', false);
}
function STP_CurrentTagSet($separator=', ') {
	single_tag_title();
}

function STP_GetTagTabs($linkformat=null, $tagseparator=null, $include_cats=null, $sort_order=null, $display_max=null, $display_min=null, $scale_max=null, $scale_min=null, $notagstext=null, $max_color=null, $min_color=null, $max_size=null, $min_size=null, $unit=null, $limit_days=null, $limit_cat=null, $exclude_cat=null) {
	return '';
}

function STP_TagTabs($linkformat=null, $tagseparator=null, $include_cats=null, $sort_order=null, $display_max=null, $display_min=null, $scale_max=null, $scale_min=null, $notagstext=null, $max_color=null, $min_color=null, $max_size=null, $min_size=null, $unit=null, $limit_days=null, $limit_cat=null, $exclude_cat=null) {
	return '';
}
?>