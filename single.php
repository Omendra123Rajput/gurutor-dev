<?php 
get_header();
global $post;
$author_id = $post->post_author; 
$author_name  =  get_the_author_meta( 'display_name', get_the_author_meta('ID')  );
$first_name  =  get_the_author_meta( 'first_name', get_the_author_meta('ID')  );
$str_name = strtolower($first_name);
$user = wp_get_current_user();
$parent_title = get_the_title($post->post_parent);
$post_data = get_post($post->post_parent);
$parent_slug = $post_data->post_name;
$parent_slug_replace = str_replace("-"," ",$parent_slug);
$updated_date = get_the_modified_time( 'F j, Y' );
$categories = get_the_category();
?>
<style>
    .grid-container {
        max-width: 100% !important;
    }
    h1, h2, h3, h4, h5, h6, a, li, p {
        font-family: "Nunito Sans", sans-serif;
    }
    header.entry-header p.entry-meta span.entry-author {
        margin-left: 5px;
        margin-right: 5px;
    }
    header.entry-header h1.entry-title {
        margin-bottom: 20px;
        font-size: 40px;
        color: #00409e;
        font-weight: 900;
    }
    .site-content {
        display: block;
    }
    .single_top_section {
        padding-top: 35px;
    }
    .post-content .wp-block-image{
        margin-bottom:20px;
    }
    .col-lg-2,.col-lg-6{
        position:relative;
        min-height:1px
    }
    .more_latest_blogs_item .image img,.suggest_post_box .image img{
        position:absolute;
        object-fit:cover;
        left:0;
        height:100%;
        width:100%
    }
    .article_content p,.suggest_post_box .c_content h3 a{
        -webkit-line-clamp:2
    }
    .breadcrumbs {
        font-size: 16px;
        border-bottom: 1px solid #eee;
        margin-bottom: 0;
        padding-bottom: 20px;
    }
    .inline_heading h3,.single_top_section .breadcrumb li a{
        text-transform:capitalize
    }
    .featured_image,header.entry-header{
        margin-bottom:20px
    }
    .single .entry-title{
        font-size:42px;
        letter-spacing:0
    }
    .page .site-inner{
        margin:0 auto;
        max-width:100%!important;
        padding:0!important
    }
    .single_top_section .breadcrumb,div#respond{
        margin:0
    }
    .single_top_section .breadcrumb li{
        display:inline-block;
        margin-bottom:6px
    }
    .more_latest_blogs_item .c_content h3 a,.more_latest_blogs_item .c_content p,.suggest_post_box .c_content h3 a{
        display:-webkit-box;
        overflow:hidden;
        text-overflow:ellipsis;
        -webkit-box-orient:vertical
    }
    #toc li.nav-active a{
        color:#8b0000;
        font-weight:500
    }
    figure.wp-block-table table tr td{
        font-size:14px;
        line-height:18px;
        word-break:break-word;
        vertical-align:middle
    }
    .blog-banner{
        text-align:center;
        padding:150px 20px;
        background:center/cover #ccc
    }
    .blog-banner h2{
        font-size:21px!important;
        font-weight:500;
        color:#fff;
        padding:0;
        margin:0!important
    }
    .blog-breadcrumbs,.blog-breadcrumbs a{
        font-size:14px;
        font-weight:400;
        color:#a1a1a1
    }
    .blog-breadcrumbs{
        padding:8px 20px
    }
    .single-banner h2{
        max-width:900px;
        margin:0 auto!important
    }
    .blog-content{
        max-width:900px;
        padding:60px;
        margin:0 auto
    }
    .blog-content strong{
        font-weight:400!important
    }
    .blog-content,.blog-content p{
        color:#444;
        font-size:17px;
        line-height:30px;
        font-weight:400
    }
    .blog-content h2{
        font-size:35px!important;
        font-weight:400;
        color:#333
    }
    .blog-content h3{
        font-size:30px!important;
        font-weight:400;
        color:#333
    }
    .blog-content a{
        color:#f68525
    }
    .blog-content figure{
        margin:0 auto;
        padding-top:40px;
        padding-bottom:40px
    }
    .author-bio{
        background:#fff;
        padding:20px 50px;
        margin-top:30px;
        margin-bottom:30px
    }
    .author-bio-inner{
        display:flex;
        align-items:center;
        justify-content:center
    }
    .author-bio-inner .author-name{
        font-size:24px;
        color:#f68525;
        margin-bottom:5px!important
    }
    .author-bio-inner img{
        margin-right:30px;
        box-shadow:1px 1px 5px 0 rgb(0 0 0 / 27%);
        border:5px solid #fff;
        width:114px;
        height:114px;
        border-radius:50%
    }
    .comment-form .form-submit .submit:hover {
        background: #8b0000;
        border-color: #8b0000;
    }
    @media only screen and (max-width:1120px){
        .blog-content{
            padding:40px
        }
        .blog-content,.blog-content p{
            font-size:16px;
            line-height:25px
        }
        .blog-content h2{
            font-size:32px!important
        }
        .blog-content h3{
            font-size:28px!important
        }
    }
    @media only screen and (max-width:1024px){
        .blog-content{
            padding:40px 30px
        }
        .blog-content,.blog-content p{
            font-size:15px;
            line-height:24px
        }
        .blog-content h2{
            font-size:28px!important
        }
        .blog-content h3{
            font-size:25px!important
        }
        .single_top_section{
            margin-top:0
        }
    }
    @media only screen and (max-width:800px){
        .author-box {
            margin-left: 0px;
        }
        .author-bio-inner{
            display:block;
            text-align:center
        }
    }
    @media only screen and (max-width:600px){
        .blog-content{
            padding:40px 20px
        }
        .blog-content,.blog-content p{
            font-size:14px;
            line-height:23px
        }
        .blog-content h2{
            font-size:25px!important
        }
        .blog-content h3{
            font-size:18px!important
        }
    }
    .more_latest_blogs_item{
        background:#fff;
        -webkit-box-shadow:0 1px 3px rgb(0 0 0 / 20%);
        box-shadow:0 1px 3px rgb(0 0 0 / 20%);
        overflow:hidden;
        background-image:-webkit-gradient(linear,left top,left bottom,from(#fff),to(#f3f3f363));
        background-image:linear-gradient(180deg,#fff,#f3f3f363);
        margin-bottom:30px
    }
    .more_latest_blogs_item .image{
        position:relative;
        padding-top:55.56%
    }
    .more_latest_blogs_item .image img{
        top:0
    }
    .more_latest_blogs_item .c_content{
        padding:17px;
        margin:0!important
    }
    .more_latest_blogs_item .c_content h3 a{
        color:#333;
        -webkit-line-clamp:2;
        text-decoration:none;
        font-weight:600
    }
    .more_latest_blogs_item .c_content h3{
        font-size:16px!important;
        line-height:26px;
        font-weight:600;
        margin-bottom:10px
    }
    .more_latest_blogs_item .c_content a.rm_link{
        display:block;
        font-size:16px
    }
    .more_latest_blogs_item .c_content p{
        font-size:15px;
        line-height:28px;
        -webkit-line-clamp:3;
        margin-bottom:10px;
        min-height:84px
    }
    .more_latest_blogs{
        padding-bottom:60px
    }
    .col-lg-6{
        width:100%;
        padding-right:8px;
        padding-left:8px
    }
    .toc_overlay,.toc_sidebar{
        position:fixed;
        top:0;
        transition:.5s linear
    }
    @media (min-width:1200px){
        .col-lg-6{
            -webkit-box-flex:0;
            -ms-flex:0 0 50%;
            flex:0 0 50%;
            max-width:50%
        }
    }
    .suggest_post_box{
        display:flex;
        justify-content:space-between;
        margin-bottom:30px
    }
    .suggest_post_box .image{
        width:40%;
        position:relative;
        padding-top:20%
    }
    .suggest_post_box .image img{
        top:0;
        border-radius:10px
    }
    .suggest_post_box .c_content{
        margin:0!important;
        padding-left:15px
    }
    .suggest_post_box .c_content h3{
        font-size:18px!important;
        margin:0 0 8px;
        line-height:1.4
    }
    .suggest_post_box .c_content p{
        margin:0;
        font-size:14px;
        font-weight:300
    }
    .suggest_post{
        margin-bottom:50px
    }
    .suggest_post_box:hover a{
        color:#333
    }
    @media(max-width:767px){
        .inline_heading h3{
            font-size:24px!important;
            margin-bottom:25px!important
        }
        .suggest_post_box .c_content h3{
            font-size:16px!important
        }
        .more_latest_blogs .slick-next{
            right:0
        }
        .more_latest_blogs .slick-prev{
            left:0
        }
        .more_latest_blogs{
            padding-bottom:50px
        }
        .more_latest_blogs .slick-arrow{
            width:40px;
            height:40px;
            padding:0;
            border:1px solid #eee;
            color:transparent;
            font-size:0px;
            position:absolute;
            top:auto;
            transform:none;
            bottom:0
        }
    }
    .single .Mybreadcrumbs{
        margin-bottom:0!important
    }
    button.btn_toc{
        border:none;
        padding:10px 12px;
        box-shadow:none;
        position:relative;
        background:#eee;
        color:#000;
        margin-bottom:10px;
        font-size:15px;
        outline:#ccc solid 1px
    }
    button.btn_toc:hover{
        background:#8b0000;
        color:#fff
    }
    button.btn_toc:hover span,button.btn_toc:hover span:before{
        border-color:#fff
    }
    button.btn_toc span{
        position:relative;
        width:16px;
        height:14px;
        display:inline-block;
        border-top:2px solid #000;
        border-bottom:2px solid #000;
        margin-right:10px;
        vertical-align:middle;
        top:-2px
    }
    button.btn_toc span:before{
        content:"";
        position:absolute;
        top:50%;
        left:0;
        transform:translateY(-50%);
        width:100%;
        height:1px;
        border:1px solid #000
    }
    .large_container{
        width:100%;
        margin-right:auto;
        margin-left:auto;
        max-width:1280px
    }
    .toc_sidebar{
        left:-300px;
        max-width:300px;
        z-index:99999
    }
    .toc_overlay{
        left:0;
        width:100%;
        height:100%;
        background:#333333d1;
        display:none
    }
    .toc_sidebar.open{
        left:0;
        transition:.5s linear
    }
    .toc_sidebar.open .toc_overlay{
        display:block;
        transition:.5s linear
    }
    #toc ul li:before,.post-content p:empty,.sidebar_widget_content .article_content .image{
        display:none
    }
    .page_content{
        padding:40px 0
    }
    .col-lg-2,.col-lg-3,.col-lg-4{
        padding-right:12px;
        padding-left:12px
    }
    .row{
        display:-webkit-box;
        display:-ms-flexbox;
        display:flex;
        -ms-flex-wrap:wrap;
        flex-wrap:wrap;
        margin-right:-12px;
        margin-left:-12px
    }
    .col-lg-2{
        width:100%;
        -webkit-box-flex:0;
        -ms-flex:0 0 22%;
        flex:0 0 22%;
        max-width:22%
    }
    .col-lg-3,.col-lg-4,.col-lg-44{
        position:relative;
        width:100%;
        min-height:1px;
        -webkit-box-flex:0
    }
    .col-lg-3,.col-lg-4{
        -ms-flex:0 0 25%;
        flex:0 0 25%;
        max-width:25%
    }
    .col-lg-44{
        -ms-flex:0 0 33.33%;
        flex:0 0 33.33%;
        max-width:33.33%;
        padding-right:12px;
        padding-left:12px
    }
    .col-lg-8,.col-lg-9{
        -webkit-box-flex:0;
        position:relative;
        width:100%;
        min-height:1px;
        padding-right:12px;
        padding-left:12px
    }
    .col-lg-8{
        -ms-flex:0 0 56%;
        flex:0 0 56%;
        max-width:56%
    }
    .col-lg-9{
        -ms-flex:0 0 75%;
        flex:0 0 75%;
        max-width:75%
    }
    .col-sm-6{
        position:relative;
        width:100%;
        min-height:1px;
        -webkit-box-flex:0;
        -ms-flex:0 0 50%;
        flex:0 0 50%;
        max-width:50%;
        padding-right:12px;
        padding-left:12px
    }
    .px_10{
        padding-right:8px;
        padding-left:8px
    }
    .table_of_content{
        background:#fff;
        box-shadow:0 1px 3px rgb(0 0 0 / 20%);
        margin-bottom:20px
    }
    .sidebar_widget_row .widget_text,.widget_box{
        -webkit-box-shadow:0 1px 3px rgb(0 0 0 / 20%)
    }
    .sticky_widget{
        position:-webkit-sticky;
        position:sticky;
        top:90px;
    }
    .sidebar_widget_row #media_image-2{
        position:-webkit-sticky;
        position:sticky;
        top:40px
    }
    #toc ul {
        padding: 0 15px;
        list-style: none;
        margin-left: 0;
    }
    .table_of_content .title{
        margin-bottom:0;
        padding:10px;
        font-size:17px;
        font-weight:700;
        text-transform:uppercase;
        text-align:center;
        background:#fff;
        position:relative;
        letter-spacing: 0.5px;
    }
    .table_of_content .toc_content{
        position:relative;
        background:#fff
    }
    .table_of_content ol li{
        display:block
    }
    .table_of_content ol li a{
        color:#333;
        display:block;
        font-size:14px;
        padding:8px 10px;
        border-left:3px solid #f7f7f7
    }
    .table_of_content a:focus{
        outline:0
    }
    .sidebar_widget .title{
        font-size:18px;
        font-weight:900;
        padding:15px;
        color:#000;
        text-transform:uppercase
    }
    .sidebar_widget_content .article_content{
        margin-top:20px
    }
    .articletitle a{
        display:block;
        margin-bottom:5px;
        font-size:14px;
        line-height:1.5;
        color:#000;
        font-weight:500;
        text-decoration:none
    }
    .article_content p{
        font-size:14px;
        overflow:hidden;
        text-overflow:ellipsis;
        display:-webkit-box;
        -webkit-box-orient:vertical;
        margin-bottom:0
    }
    .sidebar_widget_row .widget_text{
        margin-bottom:20px;
        padding:15px 10px;
        background:#fff;
        box-shadow:0 1px 3px rgb(0 0 0 / 20%)
    }
    .sidebar_widget_row .widget_text .widget-title {
        font-size: 17px;
        font-weight: 700;
        margin-bottom: 12px;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .sidebar_widget_row .widget ul{
        padding:0
    }
    .sidebar_widget_row .widget ul li {
        padding-bottom: 0;
        margin-bottom: 10px;
        font-size: 16px;
    }
    .sidebar_widget_row .widget ul li a {
        display: block;
        color: #222;
    }
    header.entry-header p{
        font-size:17px;
        margin-bottom:0;
        font-style:italic
    }
    header.entry-header p b{
        font-style:normal
    }
    .page_content .post-content h2 {
        font-size: 30px !important;
        line-height: 1.35 !important;
    }
    .page_content .post-content .wp-block-list {
        margin-left: 2rem;
    }
    .page_content .post-content h3 {
        font-size: 26px !important;
        line-height: 1.35 !important;
        color: #00409E !important;
    }
    #toc{
        max-height:78vh;
        overflow-y:auto
    }
    #toc ul li a{
        display:block;
        font-size:14px;
        color:#000;
        font-weight:400;
        position:relative;
        padding-left:20px;
        text-decoration:none
    }
    #toc ul li.nav-h2 a:before{
        content:"";
        position:absolute;
        top:7px;
        left:0;
        width:8px;
        height:8px;
        background:#00409E
    }
    #toc ul li.nav-h3 a:before{
        content:"";
        position:absolute;
        top:9px;
        left:0;
        width:8px;
        height:8px;
        display:inline-block;
        border-right:2px solid #00409E;
        border-bottom:2px solid #00409E;
        transform:rotate(-45deg)
    }
    #toc ul li a:focus,#toc ul li a:hover,.articletitle a:focus,.articletitle a:hover{
        color:#00409E
    }
    #toc::-webkit-scrollbar,.sidebar_widget_content::-webkit-scrollbar{
        width:5px
    }
    #toc::-webkit-scrollbar-track,.sidebar_widget_content::-webkit-scrollbar-track{
        background:#ddd
    }
    #toc::-webkit-scrollbar-thumb,.sidebar_widget_content::-webkit-scrollbar-thumb{
        background:#00409E
    }
    #toc ul li.nav-h3{
        margin-left:15px
    }
    .article_content .date{
        font-size:12px;
        color:#333;
        font-weight:400
    }
    .article_content .image img{
        border-radius:8px
    }
    .site-inner{
        background:#fff
    }
    .widget_box{
        margin-bottom:20px;
        padding:15px;
        background-image:-webkit-gradient(linear,left top,left bottom,from(#fff),to(#d1ebdda6));
        background-image:linear-gradient(180deg,#fff,#d1ebdda6);
        box-shadow:0 1px 3px rgb(0 0 0 / 20%)
    }
    .related_post{
        margin-top:60px
    }
    .inline_heading h3{
        font-size:24px;
        font-weight:700;
        margin-bottom:20px
    }
    .widget_box .title{
        text-align:center;
        font-size:16px;
        font-weight:700;
        text-transform:uppercase
    }
    .icon_list{
        text-align:center;
        display:block;
        margin-top:20px
    }
    .icon_list:focus,.icon_list:hover{
        outline:0;
        border:none
    }
    .icon_list i{
        font-size:18px;
        margin-bottom:10px;
        display:block;
        color:#fda841
    }
    .icon_list span{
        display:block;
        text-align:center;
        font-size:12px;
        font-weight:400;
        line-height:15px;
        max-width:100px;
        margin:0 auto
    }
    .single-post .site-inner{
        max-width:100%;
        margin:0;
        padding:0
    }
    .page_content ol li,.page_content ul li{
        margin-bottom:15px;
        font-size:16px
    }
    .author_box{
        margin-bottom:20px;
        padding:15px 15px 15px 180px;
        background:#fff;
        -webkit-box-shadow:0 1px 3px rgb(0 0 0 / 20%);
        box-shadow:0 1px 3px rgb(0 0 0 / 20%);
        position:relative
    }
    .author_box img{
        position:absolute;
        top:50%;
        left:20px;
        max-width:140px;
        transform:translateY(-50%);
        border-radius:7px
    }
    .author_bio .title{
        font-weight:600;
        font-size:20px;
        margin-bottom:10px;
        line-height:30px
    }
    .author_bio .title a{
        color:#333;
        text-decoration:none
    }
    .author_bio p{
        margin:0;
        font-size:14px
    }
    .author-box-title {
        font-weight: 700;
    }
    .page_content .comment-respond .comment-reply-title {
        font-size: 26px !important;
        color: #00409E !important;
        font-weight: 700 !important;
        margin-bottom: 15px;
    }
    .comment-form #author, .comment-form #email, .comment-form #url {
        display: block;
        width: 100%;
    }
    .author-box {
        background-color: #f4f4f4;
        font-size: 14px;
        line-height: 1.87;
        padding: 35px 70px;
        margin-left: 40px;
        margin-bottom: 30px;
        min-height: 200px;
		margin-top: 30px;
    }
    .author-box .avatar {
        border: 7px solid #fff;
        border-radius: 0;
        box-shadow: 0 9px 45px rgba(0,0,0,.14);
        transform: translate3d(-35px,0,0);
        margin: 0 15px 35px -70px;
        float: left;
        position: relative;
        z-index: 2;
        width: 140px;
    }
    .author-box-title {
        font-weight: 700;
        color: #222;
        font-size: 26px;
        line-height: 1.23;
        margin-bottom: 20px;
    }
    .author-box p {
		margin-bottom: 0;
		padding-left: 86px;
		font-size: 16px;
		line-height: 30px;
	}
    @media(max-width:1140px){
        .large_container{
            padding:0 15px
        }
    }
    @media(max-width:992px){
        .author_box{
            padding:15px
        }
        .author_box img{
            position:relative;
            top:auto;
            left:auto;
            transform:none
        }
        .author_bio p{
            font-size:15px
        }
        .related_post{
            margin-top:40px
        }
        .col-lg-2,.col-lg-3,.col-lg-4,.col-lg-44,.col-lg-8,.col-lg-9{
            -ms-flex:0 0 100%;
            flex:0 0 100%;
            max-width:100%
        }
        .toc_content{
            display:none;
            padding-top:16px
        }
        .table_of_content .title{
            position:relative;
            margin:0;
            cursor:pointer;
            font-size:15px
        }
        .table_of_content .title:after{
            content:'';
            width:14px;
            height:14px;
            color:#000;
            display:inline-block;
            border-right:1px solid #000;
            border-bottom:1px solid #000;
            transform:rotate(45deg);
            position:absolute;
            top:10px;
            right:20px
        }
        .toc_content.show{
            display:block
        }
        .table_of_content.fixed{
            position:fixed;
            top:0;
            left:0;
            width:100%;
            z-index:99;
            border-bottom:1px solid #ddd;
            background:#eee
        }
        .author-bio{
            padding:18px 10px;
            margin-top:20px;
            margin-bottom:20px
        }
        .author-bio-inner img{
            margin-right:0;
            margin-bottom:10px
        }
        .author-bio-inner p:last-child{
            margin:0
        }
    }
    @media(max-width:767px){
        .page_content{
            padding:30px 0 0
        }
        header.entry-header h1.entry-title {
            font-size: 33px;
        }
        .page_content .post-content h2 {
            font-size: 27px !important;
        }
        .page_content .post-content h3 {
            font-size: 24px !important;
        }
        div#respond {
            margin-bottom: 30px;
        }
		.author-box .avatar {
			margin: 0 auto 20px;
			float: none;
			transform: none;
		}
		.author-box {
			padding: 25px;
			margin-left: 0;
		}
		.author-box p {
			padding-left: 0;
		}
    }
</style>
<div class="single_top_section">
    <div class="large_container">
        <div class="breadcrumbs">
            <span>
                <span><a href="<?php echo site_url(); ?>">Home</a></span> » 
                <?php
                $category = get_the_category();
                if ( !empty($category) ) {
                    $cat_link = get_category_link($category[0]->term_id);
                    $cat_name = esc_html($category[0]->name);
                    echo '<span><a href="' . esc_url($cat_link) . '">' . $cat_name . '</a></span> » ';
                }
                ?>
                <span class="breadcrumb_last"><?php the_title(); ?></span>
            </span>
        </div>
    </div>
</div>
<div class="large_container">
    <div class="page_content">
        <div class="row">
            <div class="col-lg-2">
                <div class="table_of_content sticky_widget">
                    <div class="title">Table of Content</div>
                    <div class="toc_content">
                        <div id="toc"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <article>
                    <div class="post-content">
                        <header class="entry-header">
                            <h1 class="entry-title"><?= get_the_title() ?></h1>
                            <p class="entry-meta"><time class="entry-time"><?php echo $updated_date ?></time> By <span class="entry-author"><a href="<?php echo get_author_posts_url(get_the_author_meta('ID')) ?>" class="entry-author-link" target="_blank"><span class="entry-author-name"><?php the_author();?></span></a></span> <span class="entry-comments-link"><a href="#respond">Leave a Comment</a></span></p>
                        </header>
                        <div class="featured_image">
                            <?php if (has_post_thumbnail()) : ?>
                                <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'full'); ?>" alt="<?php the_title_attribute(); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="entry-content" id="wrapper">
                            <?php the_content(); ?>
                        </div>
                        <section class="author-box">
                            <?php
                            $author_id = get_the_author_meta('ID');
                            ?>
                            <img src="https://gurutor.co/wp-content/uploads/2024/08/Matthew-portrait-01-1500px-min.jpg" alt="matthew-brandon" class="avatar avatar-124 photo" />
                            <div class="author-box-title"><?php the_author();?></div>
                            <div class="author-box-content">
                                <p><?php echo get_the_author_meta('description'); ?></p>
                            </div>
                        </section>
                    </div>
                </article>

                <?php echo comment_form(); ?>
            </div>
            <div class="col-lg-2 sidebar_widget_row">
                <section class="widget widget_text sticky_widget">
                    <div class="widget-wrap">
                        <div class="widget-title widgettitle">Latest Posts</div>
                        <div class="textwidget">
                            <ul>
                                <?php
                                $recent_posts = wp_get_recent_posts(array(
                                    'numberposts' => 10,
                                    'post_status' => 'publish'
                                ));
                                foreach ($recent_posts as $post) :
                                ?>
                                    <li>
                                        <a href="<?php echo get_permalink($post['ID']); ?>">
                                            <?php echo esc_html($post['post_title']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<script>
	jQuery(function($){
		$(".faq_box  .faq__title").click(function(){
			$(this).next().slideToggle();
		})
	})
</script>
<?php 
echo get_footer();
?>