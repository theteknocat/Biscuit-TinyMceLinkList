<?php
if (!empty($pages[$current_parent_id])) {
	$index = 1;
	foreach ($pages[$current_parent_id] as $page) {
		if ($page->user_can_access()) {
			$slug_bits = explode('/',$page->slug());
			$indent = count($slug_bits)-1;
			$indent_str = str_repeat('--',$indent);
			$page_title = $page->title();
			$page_title = $indent_str.addslashes(H::purify_text($page->title()));
			if ($page->access_level() > PUBLIC_USER) {
				$page_title .= ' ('.$page->access_level_name().' access required)';
			}
			?>

	['<?php echo $page_title; ?>','/canonical-page-link/<?php echo $page->id() ?>/']<?php
			if (!empty($pages[$page->id()]) && $with_children) {
				$sub_pages = $Navigation->render_pages_hierarchically($pages, $page->id(), $with_children, $view_file);
				if (!empty($sub_pages)) {
					echo ','.$sub_pages;
				}
			}
			if ($index < count($pages[$current_parent_id])) {
				echo ',';
			}
		}
		$index++;
	}
}
?>