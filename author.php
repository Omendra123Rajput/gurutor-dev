<?php get_header(); ?>
<style>
    .container.grid-container {
        width: auto;
        max-width: 100%;
        padding: 0px !important;
    }
    .site-content {
        display: block;
    }
	.breadcrumb {
	    border: none;
	    padding: 0;
	    margin: 0;
	    font-size: 14px;
	}
	.breadcrumb li {
        display: inline-block;
        margin-right: 3px;
        color: #fff;
        font-size: 16px;
    }
	.breadcrumb li a {
	    padding-right: 3px;
	    text-decoration: none;
	    color: #fff;
	}
	.row{
		display:-webkit-box;
		display:-ms-flexbox;
		display:flex;
		-ms-flex-wrap:wrap;
		flex-wrap:wrap;
		margin-right:-15px;
		margin-left:-15px
	}
	.col,.col-1,.col-10,.col-11,.col-12,.col-2,.col-3,.col-4,.col-5,.col-6,.col-7,.col-8,.col-9,.col-auto,.col-lg,.col-lg-1,.col-lg-10,.col-lg-11,.col-lg-12,.col-lg-2,.col-lg-3,.col-lg-4,.col-lg-5,.col-lg-6,.col-lg-7,.col-lg-8,.col-lg-9,.col-lg-auto,.col-md,.col-md-1,.col-md-10,.col-md-11,.col-md-12,.col-md-2,.col-md-3,.col-md-4,.col-md-5,.col-md-6,.col-md-7,.col-md-8,.col-md-9,.col-md-auto,.col-sm,.col-sm-1,.col-sm-10,.col-sm-11,.col-sm-12,.col-sm-2,.col-sm-3,.col-sm-4,.col-sm-5,.col-sm-6,.col-sm-7,.col-sm-8,.col-sm-9,.col-sm-auto,.col-xl,.col-xl-1,.col-xl-10,.col-xl-11,.col-xl-12,.col-xl-2,.col-xl-3,.col-xl-4,.col-xl-5,.col-xl-6,.col-xl-7,.col-xl-8,.col-xl-9,.col-xl-auto{
		position:relative;
		width:100%;
		min-height:1px;
		padding-right: 15px;
		padding-left:15px
	}
	.align-self-center {
	    -ms-flex-item-align: center;
	    align-self: center;
	}
	.sm_title {
	    font-size: 22px;
	    font-weight: 500;
	}
	.post_date {
        font-size: 16px;
        text-transform: capitalize;
        margin-bottom: 10px;
        font-weight: 400;
        margin-top: 15px;
        color: #333;
    }
    .post_date a {
        color: #333;
        text-decoration: none;
    }
	.container{
	    width:100%;
	    max-width: 1140px;
	    margin-right:auto;
	    margin-left:auto
	}
	@media (min-width:1024px){
	    .col-lg-2{
	        -webkit-box-flex:0;
	        -ms-flex:0 0 16.66667%;
	        flex:0 0 16.66667%;
	        max-width:16.66667%
	    }
	    .col-lg-3{
	        -webkit-box-flex:0;
	        -ms-flex:0 0 25%;
	        flex:0 0 25%;
	        max-width:25%
	    }
	    .col-lg-4{
	        -webkit-box-flex:0;
	        -ms-flex:0 0 33.33333%;
	        flex:0 0 33.33333%;
	        max-width:33.33333%
	    }
	    .col-lg-5{
	        -webkit-box-flex:0;
	        -ms-flex:0 0 41.66667%;
	        flex:0 0 41.66667%;
	        max-width:41.66667%
	    }
	    .col-lg-6{
	        -webkit-box-flex:0;
	        -ms-flex:0 0 50%;
	        flex:0 0 50%;
	        max-width:50%
	    }
	    .col-lg-7{
	        -ms-flex:0 0 58.33333%;
	        flex:0 0 58.33333%;
	        max-width:58.33333%
	    }
	    .col-lg-8{
	        -webkit-box-flex:0;
	        -ms-flex:0 0 66.66667%;
	        flex:0 0 66.66667%;
	        max-width:66.66667%
	    }
	    .col-lg-9{
	        -webkit-box-flex:0;
	        -ms-flex:0 0 75%;
	        flex:0 0 75%;
	        max-width:75%
	    }
	    .col-lg-10{
	        -ms-flex:0 0 83.333333%;
	        flex:0 0 83.333333%;
	        max-width:83.333333%
	    }
	    .col-lg-12{
	        -ms-flex:0 0 100%;
	        flex:0 0 100%;
	        max-width:100%
	    }
	}
	.section_content h1 {
	    font-size: 28px;
	    text-transform: capitalize;
	    font-weight: 600;
	    color: #fff;
	}
	.section_content p {
        font-size: 18px;
        color: #fff;
        font-weight: 600;
        margin-bottom: 0px;
    }
    .section_content img {
        width: 210px;
        border-radius: 20px;
    }
	.blog_box .image {
        position: relative;
        padding-top: 48%;
    }
	.blog_box .image img {
        width: 100%;
        height: 100%;
        border-radius: 20px;
        transition: all 0.5s ease-in-out;
        position: absolute;
        top: 0;
        left: 0;
    }
	.blog_box .ccontent h3 {
	    font-size: 18px;
	    overflow: hidden;
	    text-overflow: ellipsis;
	    display: -webkit-box;
	    -webkit-line-clamp: 2;
	    -webkit-box-orient: vertical;
	    margin-bottom: 12px;
	}
	.blog_box .ccontent h3 a {
        font-weight: 800;
        line-height: 1.5;
        color: #222;
        text-decoration: none;
        font-size: 21px;
    }
	.blog_box a:hover, .blog_box a:focus {
	    outline: none;
	}
	.blog_box .ccontent h3 a:hover {
	    color: var(--body-copy-color);
	}
	.blog_box .ccontent p {
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        font-size: 18px;
        margin-bottom: 10px;
    }
	.blog_box .ccontent .rmore_link {
        color: #222;
        font-size: 16px;
        text-transform: capitalize;
        font-weight: 500;
        text-decoration: none;
    }
	svg.kadence-svg-icon.kadence-arrow-right-alt-svg {
        top: 10px;
        position: relative;
        width: 1em;
        margin-left: 4px;
    }
	.blog_box .ccontent .rmore_link:hover {
	    color: var(--body-copy-color);
	}
	.button_readmore {
	    display: inline-block;
	    padding: 10px 20px;
	    background: #4a8396;
	    color: #fff;
	    min-width: 160px;
	    text-align: center;
	    font-size: 18px;
	}
	.button_readmore:hover, .button_readmore:focus {
	    outline: none;
	    background: var(--body-copy-color);
	    color: #fff !important;
	}
	.mb_30 {
	    margin-bottom: 40px;
	}
	.load_more_blog {
	    display: none;
	}
	.load_blog {
	    display: none;
	}
	.section_content {
        padding: 50px 0 130px;
        background: url(https://gurutor.co/wp-content/uploads/2025/08/banner.png);
        background-repeat: no-repeat;
        background-size: cover;
        background-position: bottom;
        min-height: 300px;
    }
	.section_content h1 {
        font-size: 40px;
        text-transform: capitalize;
        font-weight: 800;
        margin-bottom: 14px !important;
        color: #fff;
    }
    .pagination {
        text-align: center;
        margin-bottom: 40px;
    }
    .pagination span.page-numbers.current {
        display: inline-block;
        background: var(--body-copy-color);
        padding: 5px 12px;
        font-size: 14px;
        color: #fff;
        border-radius: 4px;
    }
    .pagination a {
        display: inline-block;
        background: #eee;
        padding: 5px 12px;
        font-size: 14px;
        color: #000;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
    }
    .text-right {
        text-align: right;
    }
    .author_bio {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .author_bio .left {
        width: 75%;
    }
    h2.section_title {
        font-size: 35px !important;
        line-height: 1.3 !important;
    }
    .social_icon {
        margin-top: 16px;
    }
    .social_icon svg {
        width: 34px;
        height: 34px;
        fill: #fff;
    }
	@media(max-width:1180px){
	    .container{
	        max-width: 960px;
	    }
	}
	@media(max-width:1023px){
	    .container{
	        max-width: 750px;
	        padding-left: 5%;
	        padding-right: 5%;
	    }
	}
	@media(max-width:767px){
	    .col-sm-6{
			-webkit-box-flex:0;
			-ms-flex:0 0 50%;
			flex:0 0 50%;
			max-width:50%
		}
		.section_content {
            min-height: 240px;
            padding: 50px 0 0px;
        }
        .content_body {
            padding-top: 20px;
        }
        .author_bio {
            display: block;
        }
        .section_content h1 {
            font-size: 30px;
        }
        .author_bio .left {
            width: 100%;
            margin-bottom: 30px;
        }
        .right {
            text-align: center;
        }
        .section_content img {
            width: 100%;
        }
        h2.section_title {
            font-size: 30px !important;
            line-height: 1.2 !important;
        }
	} 
</style>
<div class="section_content">
	<div class="container">
	    <div class="author_bio">
	        <div class="left">
                <h1><?php echo get_the_author_meta( 'display_name' );?></h1>
                <p><?php echo get_the_author_meta('description'); ?></p>
                <div class="social_icon">
                    <a href="https://www.linkedin.com/in/matthew-brandon-5492a1379/" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z"/></svg>
                    </a>
                </div>
	        </div>
	        <div class="right">
                <img src="https://gurutor.co/wp-content/uploads/2024/08/Matthew-portrait-01-1500px-min.jpg" alt="<?php echo get_the_author_meta( 'display_name' );?>" />
            </div>
	    </div>
	</div>
</div>
<section class="content_body">
	<div class="container">
        <h2 class="section_title">Posts by <?php echo get_the_author_meta('display_name');?>:</h2>
    	<?php
    	$urduCurrentPage = get_query_var('paged');
        $categories = get_the_category();
        $category_id = $categories[0]->cat_ID;
        $urargs = array(
            'post_type' => 'post',
    	    'post_status' => 'publish',
    	    'posts_per_page' => 8,
            'paged' => $urduCurrentPage,
    	    'author' => $author_id
        );
        $urquerys = new WP_Query($urargs);
    	?>
        <div class="row">
        <?php
        while ( $urquerys->have_posts() ) {
            $urquerys->the_post();
            $featured_img_url = get_the_post_thumbnail_url(get_the_ID(),'full'); 
    		$post_id = get_the_ID();
    		$content = get_the_content($post_id);
    		$post_title = get_the_title($post_id);
    		$trimmed_content = wp_trim_words( $content, 22, NULL );
            ?>
            <div class="col-lg-6 col-md-6 mb_30">
				<div class="blog_box">
	                <div class="image">
	                    <a href="<?php echo get_the_permalink($post_id)?>">
	                        <img src="<?php echo $featured_img_url ?>" alt="<?php echo $post_title ?>">
	                    </a>
	                </div>
	                <div class="ccontent">
	                    <div class="post_date">posted on <time class="entry-time"><?php echo get_the_date(); ?></time> - by <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>"><?php echo get_the_author(); ?></a></div>
	                    <h3><a href="<?php echo get_the_permalink($post_id)?>"><?php echo $post_title ?></a></h3>
	                    <p><?php echo $trimmed_content ?></p>
	                    <a href="<?php echo get_the_permalink($post_id)?>" class="rmore_link">Read More <svg aria-hidden="true" class="kadence-svg-icon kadence-arrow-right-alt-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="27" height="28" viewBox="0 0 27 28"><title>Doorgaan</title><path d="M27 13.953c0 0.141-0.063 0.281-0.156 0.375l-6 5.531c-0.156 0.141-0.359 0.172-0.547 0.094-0.172-0.078-0.297-0.25-0.297-0.453v-3.5h-19.5c-0.281 0-0.5-0.219-0.5-0.5v-3c0-0.281 0.219-0.5 0.5-0.5h19.5v-3.5c0-0.203 0.109-0.375 0.297-0.453s0.391-0.047 0.547 0.078l6 5.469c0.094 0.094 0.156 0.219 0.156 0.359v0z"></path></svg></a>
	                </div>
	            </div>
			</div>
            <?php
        }
        ?>
        </div>
        <div class='pagination'>
            <?php
                echo paginate_links(array(
                   'total' =>  $urquerys->max_num_pages
                ));
            ?>
        </div>
        <?php
        /* Restore original Post Data */
        wp_reset_postdata();
        ?>
    </div>
</section>
<?php get_footer(); ?>