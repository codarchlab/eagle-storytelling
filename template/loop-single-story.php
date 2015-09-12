<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

	<?php if (et_get_option('flexible_integration_single_top') <> '' && et_get_option('flexible_integrate_singletop_enable') == 'on') echo (et_get_option('flexible_integration_single_top')); ?>
	
	<article id="post-<?php the_ID(); ?>" <?php post_class('entry clearfix'); ?>>
	<pre><?php //print_r(get_post_meta(get_the_ID())); ?></pre>
	
		<div class="story-thumbnail">
			<?php if ($url = get_post_meta(get_the_ID(), 'esa_thumbnail', true)) { ?>
				<img src="<?php echo $url; ?>" alt='<?php the_title; ?>' />
			<?php } ?>
		</div>
		
		

			<h1 class="page_title"><?php the_title(); ?></h1>
	
		<?php if(has_post_thumbnail()) { ?><div><?php } ?>
			<div class="meta-info">
				<?php esc_html_e('Posted','Flexible'); ?>
				<?php esc_html_e('by','Flexible'); ?>
				<?php /*the_author_posts_link();*/ the_author(); ?>
				<?php esc_html_e('on','Flexible'); ?>
				<?php the_time(et_get_option('flexible_date_format')) ?>
	
				
				<?php 
					$the_taxonomys = get_the_taxonomies($get_the_ID, array('template' => "<span class='tax-%s'>%l</span>"));
					echo (count($the_taxonomys['story_keyword'])) ? '<br> Keywords: ' . $the_taxonomys['story_keyword'] : '';
				?>
	
				
				<?php /*comments_popup_link(esc_html__('0 comments','Flexible'), esc_html__('1 comment','Flexible'), '% '.esc_html__('comments','Flexible'));*/ ?>
			</div>
		<?php if(has_post_thumbnail()) { ?></div><?php } ?>
		
		<?php /*
			$index_postinfo = et_get_option('flexible_postinfo2');
			if ( $index_postinfo ){
				echo '<p class="meta-info">';
				et_postinfo_meta( '', et_get_option('flexible_date_format'), esc_html__('0 comments','Flexible'), esc_html__('1 comment','Flexible'), '% ' . esc_html__('comments','Flexible') );
				echo '</p>';
				echo "d<pre>"; the_tags(); echo "</pre>";
				echo "e<pre>"; the_category(); echo "</pre>";
			}*/
		?>
		<p class="sharelink"><a class="addthis_button_compact"><span>Share &raquo;</span></a></p>

		<div class="post-excerpt">
			
		</div>
		
		<div class="post-content">
			<?php the_content(); ?>
			<?php edit_post_link(esc_attr__('Edit this page','Flexible')); ?>
			<?php
			 wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Flexible').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
		</div>
		<div style='clear:both'></div>
		
		 	<!-- end .post-content -->
	</article> <!-- end .entry -->
	
	<?php if (et_get_option('flexible_integration_single_bottom') <> '' && et_get_option('flexible_integrate_singlebottom_enable') == 'on') echo(et_get_option('flexible_integration_single_bottom')); ?>
		
	<?php 
		if ( et_get_option('flexible_468_enable') == 'on' ){
			if ( et_get_option('flexible_468_adsense') <> '' ) echo( et_get_option('flexible_468_adsense') );
			else { ?>
			   <a href="<?php echo esc_url(et_get_option('flexible_468_url')); ?>"><img src="<?php echo esc_url(et_get_option('flexible_468_image')); ?>" alt="468 ad" class="foursixeight" /></a>
	<?php 	}    
		}
	?>
	
	<?php 
		if ( 'on' == et_get_option('flexible_show_postcomments') ) comments_template('', true);
	?>
<?php endwhile; // end of the loop. ?>
