<?php include get_theme_file_path('template-parts/includes/main_tab_style.php'); ?>

  <div class="bc_tabs">
    <div class="bc_tab bc_active" data-target="quant">QUANT</div>
    <div class="bc_tab" data-target="verbal">VERBAL</div>
    <div class="bc_tab" data-target="data">DATA INSIGHTS</div>
  </div>

  <div class="bc_tab_content bc_active" id="quant">
      

    
    
    <div id="cr_algebra" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_algebra', 'algebra');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_algebra">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php 
                $section_id = "cr_algebra";
                $code = "ER";
                
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php
                    $section_id = "cr_algebra";
                    $code = "ER";
                    
                    include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    <div id="cr_quadratics" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_quadratics', 'algebra');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_quadratics">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_quadratics";
                 $code = "QD";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_quadratics";
                    $code = "QD";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    <div id="cr_main_quadratics" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_main_quadratics', 'algebra');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_main_quadratics">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_main_quadratics";
                 $code = "INEQ";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_main_quadratics";
                    $code = "INEQ";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
     <div id="cr_ffs" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_ffs', 'algebra');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_ffs">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_ffs";
                   $code = "FFS";
                   
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_ffs";
                    $code = "FFS";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="cr_linear" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_linear', 'algebra');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_linear">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_linear";
                 $code = "LE";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_linear";
                    $code = "LE";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="algebra" class="box" style="margin-top: 50px;width: 945px;">
        <?php include get_theme_file_path('template-parts/tabs/quant/header_algebra.php'); ?>
        <div style="margin-top: 40px;">
            <?php include get_theme_file_path('template-parts/tabs/quant/progress_line3.php'); ?>
        </div>
    </div>
    
    
    
    
    <div id="cr_divisibility_primes" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_divisibility_primes', 'number_properties');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_divisibility_primes">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
               $section_id = "cr_divisibility_primes";
               $code = "DP";
                       
               include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_divisibility_primes";
                    $code = "DP";
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    
    <div id="cr_digits_decimals" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_digits_decimals', 'number_properties');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_digits_decimals">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_digits_decimals";
                 $code = "DD";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_digits_decimals";
                    $code = "DD";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    <div id="number_properties" class="box" style="margin-top: 50px;width: 945px;">
        <?php include get_theme_file_path('template-parts/tabs/quant/header_123.php'); ?>
        <div style="margin-top: 40px;">
            <?php include get_theme_file_path('template-parts/tabs/quant/progress_line4.php'); ?>
        </div>
    </div>
    
    
    
    <div id="cr_translations" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_translations', 'word_problems');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_translations">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_translations";
                 $code = "TRN";
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_translations";
                    $code = "TRN";
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="cr_rates" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_rates', 'word_problems');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_rates">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_rates";
                 $code = "RTW";
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_rates";
                    $code = "RTW";
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="cr_overlapping" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_overlapping', 'word_problems');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_overlapping">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_overlapping";
                 $code = "OS";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_overlapping";
                    $code = "OS";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="cr_statistics" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_statistics', 'word_problems');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_statistics">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_statistics";
                    $code = "STS";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_statistics";
                       $code = "STS";
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    <div id="cr_evenly" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_evenly', 'word_problems');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_evenly">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_evenly";
                 $code = "ESS";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_evenly";
                    $code = "ESS";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="cr_probability" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_probability', 'word_problems');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_probability">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_probability";
                 $code = "PRB";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_probability";
                    $code = "PRB";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    <div id="cr_combinatorics" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_combinatorics', 'word_problems');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_combinatorics">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_combinatorics";
                 $code = "CMB";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_combinatorics";
                    $code = "CMB";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="word_problems" class="box" style="margin-top: 50px;width: 945px;">
        <?php include get_theme_file_path('template-parts/tabs/quant/header_a.php'); ?>
        <div style="margin-top: 40px;">
            <?php include get_theme_file_path('template-parts/tabs/quant/progress_line5.php'); ?>
        </div>
    </div>
    
    
    
    <div id="cr_fractions" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_fractions', 'fractions');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_fractions">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_fractions";
                 $code = "FR";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_fractions";
                    $code = "FR";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    <div id="cr_percentages" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_percentages', 'fractions');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_percentages">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_percentages";
                 $code = "PCT";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_percentages";
                    $code = "PCT";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
     <div id="cr_ratios" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_ratios', 'fractions');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_ratios">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_ratios";
                 $code = "RA";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_ratios";
                    $code = "RA";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    
    <div id="cr_fpr" class="box dq-none" style="margin-top: 50px;width: 945px;">
        <div style="display: flex;">
            <div style="width: 70px;height: 70px;background: #e2ecff;border-radius: 100%;margin-right: 20px;">
                <img src="<?php echo get_theme_file_uri('template-parts/icons/header.svg'); ?>" style="width: 100%;"/>
            </div>
            <div>
                <span class="cursor-pointer" style="color: #015ae2;" onclick="setBack('cr_fpr', 'fractions');">← Back</span>
                <h2 style="margin: 0px !important;color: #002434 !important;
                font-size: 42px !important;font-family: Open Sans !important;
                font-weight: bold !important;" id="detail_header_cr_fpr">Key Data</h2>
            </div>
        </div>
        

        <div style="margin-top: 40px;">
            <?php
                 $section_id = "cr_fpr";
                 $code = "FPRC";
                 
                include get_theme_file_path('template-parts/tabs/quant/progress_line.php'); ?>
            <div>
                <?php 
                    $section_id = "cr_fpr";
                    $code = "FPRC";
                    
                include get_theme_file_path('template-parts/tabs/quant/progress_line2.php'); ?>
            </div>
        </div>
    </div>
    
    <div id="fractions" class="box" style="margin-top: 50px;width: 945px;">
        <?php include get_theme_file_path('template-parts/tabs/quant/header_report.php'); ?>
        <div style="margin-top: 40px;">
            <?php include get_theme_file_path('template-parts/tabs/quant/progress_line6.php'); ?>
        </div>
    </div>
    
    
  </div>
  <div class="bc_tab_content" id="verbal">
    <?php include get_theme_file_path('template-parts/tabs/verbal/header.php'); ?>
    <div id="cr-box" class="box" style="margin-top: 50px;">
        <?php include get_theme_file_path('template-parts/tabs/verbal/index.php'); ?>
    </div>
    
    <?php include get_theme_file_path('template-parts/tabs/verbal/header2.php'); ?>
    <?php include get_theme_file_path('template-parts/statistics/reading-comprehension.php'); ?>
  </div>
  <div class="bc_tab_content" id="data">
    <div id="cr-box" class="box" style="margin-top: 50px;width: 945px;">
        <div style="margin-top: 40px;">
            <?php include get_theme_file_path('template-parts/tabs/data_insights/progress_line.php'); ?>
        </div>
    </div>
    
    <div id="cr-box" class="box" style="margin-top: 50px;width: 945px;">
         <?php include get_theme_file_path('template-parts/tabs/data_insights/header2.php'); ?>
        <div style="margin-top: 40px;">
            <?php include get_theme_file_path('template-parts/tabs/data_insights/progress_line2.php'); ?>
        </div>
    </div>
  </div>


<script>
    function getSubtopicData(code) {
        if (!window.quantStats || !window.quantStats.details) {
            console.warn("quantStats not ready yet");
            return null;
        }
        return window.quantStats.details[code] || null;
    }
    

    function hideWithAnimation(el) {
      el.classList.add("dq-none"); // make visible
      el.classList.remove("fade-section", "fade-out"); // start hidden
    
      // let browser paint, then remove fade-out to trigger transition
      requestAnimationFrame(() => {
        el.classList.remove("fade-out");
        el.classList.add("fade-in");
    
        // cleanup after transition
        el.addEventListener("transitionend", function handler() {
          el.classList.remove("fade-in");
          el.removeEventListener("transitionend", handler);
        });
      });
    }

    
    function showWithAnimation(el) {
      el.classList.remove("dq-none"); // make visible
      el.classList.add("fade-section", "fade-out"); // start hidden
    
      // let browser paint, then remove fade-out to trigger transition
      requestAnimationFrame(() => {
        el.classList.remove("fade-out");
        el.classList.add("fade-in");
    
        // cleanup after transition
        el.addEventListener("transitionend", function handler() {
          el.classList.remove("fade-in");
          el.removeEventListener("transitionend", handler);
        });
      });
    }

    
    function setDetails(section_id, section_main_id, detail_header = "Key Dataqqq") {
    //   localStorage.setItem("detail_header_" + section_id, detail_header);
    //   localStorage.setItem("detail_correct_overall_" + section_id, detail_correct_overall);
    //   localStorage.setItem("detail_correct_when_receiving_" + section_id, detail_correct_when_receiving);
    //   localStorage.setItem("detail_correct_with_no_" + section_id, detail_correct_with_no);
    //   localStorage.setItem("detail_avg_time_" + section_id, detail_avg_time);
    //   localStorage.setItem("detail_correct_step1_" + section_id, detail_correct_step1);
    //   localStorage.setItem("detail_correct_step2a_" + section_id, detail_correct_step2a);
    //   localStorage.setItem("detail_correct_step2b_" + section_id, detail_correct_step2b);
    //   localStorage.setItem("detail_is_setted_" + section_id, true);
      
    //   const saved_detail_header = localStorage.getItem("detail_header_" + section_id);
    //   const saved_detail_correct_overall = localStorage.getItem("detail_correct_overall_" + section_id);
    //   const saved_detail_correct_when_receiving = localStorage.getItem("detail_correct_when_receiving_" + section_id);
    //   const saved_detail_correct_with_no = localStorage.getItem("detail_correct_with_no_" + section_id);
    //   const saved_detail_avg_time = localStorage.getItem("detail_avg_time_" + section_id);
    //   const saved_detail_correct_step1 = localStorage.getItem("detail_correct_step1_" + section_id);
    //   const saved_detail_correct_step2a = localStorage.getItem("detail_correct_step2a_" + section_id);
    //   const saved_detail_correct_step2b = localStorage.getItem("detail_correct_step2b_" + section_id);
      
      const sectionMain = document.getElementById(section_main_id);
      const section = document.getElementById(section_id);
    
      hideWithAnimation(sectionMain);
      showWithAnimation(section);
      
      document.getElementById("detail_header_" + section_id).textContent = detail_header;
      
    //   document.getElementById("detail_correct_overall_" + section_id).setAttribute("data-percentage", saved_detail_correct_overall);
    //   document.getElementById("detail_correct_when_receiving_" + section_id).setAttribute("data-percentage", saved_detail_correct_when_receiving);
    //   document.getElementById("detail_correct_with_no_" + section_id).setAttribute("data-percentage", saved_detail_correct_with_no);
    //   document.getElementById("bc_timer-count-1_" + section_id).textContent = saved_detail_avg_time;
    //   document.getElementById("detail_correct_step1_" + section_id).setAttribute("data-percentage", saved_detail_correct_step1);
    //   document.getElementById("detail_correct_step2a_" + section_id).setAttribute("data-percentage", saved_detail_correct_step2a);
    //   document.getElementById("detail_correct_step2b_" + section_id).setAttribute("data-percentage", saved_detail_correct_step2b);
      
      
    }
    
    // function setDetails(
    //   section_id,
    //   section_main_id,
    //   detail_header = "Key Dataqqq",
    //   detail_correct_overall = 89,
    //   detail_correct_when_receiving = 93,
    //   detail_correct_with_no = 87,
    //   detail_avg_time = 127,
    //   detail_correct_step1 = 12,
    //   detail_correct_step2a = 45,
    //   detail_correct_step2b = 78
    // ) {
    //   // Save values to localStorage
    //   localStorage.setItem("detail_header_" + section_id, detail_header);
    //   localStorage.setItem("detail_correct_overall_" + section_id, detail_correct_overall);
    //   localStorage.setItem("detail_correct_when_receiving_" + section_id, detail_correct_when_receiving);
    //   localStorage.setItem("detail_correct_with_no_" + section_id, detail_correct_with_no);
    //   localStorage.setItem("detail_avg_time_" + section_id, detail_avg_time);
    //   localStorage.setItem("detail_correct_step1_" + section_id, detail_correct_step1);
    //   localStorage.setItem("detail_correct_step2a_" + section_id, detail_correct_step2a);
    //   localStorage.setItem("detail_correct_step2b_" + section_id, detail_correct_step2b);
    //   localStorage.setItem("detail_is_setted_" + section_id, true);
    
    //   // Get values back (always strings from localStorage)
    //   const saved_detail_header = localStorage.getItem("detail_header_" + section_id);
    //   const saved_detail_correct_overall = Number(localStorage.getItem("detail_correct_overall_" + section_id));
    //   const saved_detail_correct_when_receiving = Number(localStorage.getItem("detail_correct_when_receiving_" + section_id));
    //   const saved_detail_correct_with_no = Number(localStorage.getItem("detail_correct_with_no_" + section_id));
    //   const saved_detail_avg_time = Number(localStorage.getItem("detail_avg_time_" + section_id));
    //   const saved_detail_correct_step1 = Number(localStorage.getItem("detail_correct_step1_" + section_id));
    //   const saved_detail_correct_step2a = Number(localStorage.getItem("detail_correct_step2a_" + section_id));
    //   const saved_detail_correct_step2b = Number(localStorage.getItem("detail_correct_step2b_" + section_id));
    
    //   const sectionMain = document.getElementById(section_main_id);
    //   const section = document.getElementById(section_id);
    
    //   hideWithAnimation(sectionMain);
    //   showWithAnimation(section);
    
    //   // Debugging check
    //   console.log("Step1:", saved_detail_correct_step1);
    //   console.log("Step2a:", saved_detail_correct_step2a);
    //   console.log("Step2b:", saved_detail_correct_step2b);
    
    //   // Helper function to safely set attributes
    //   function setAttr(id, attr, value) {
    //     const el = document.getElementById(id);
    //     if (el) {
    //       el.setAttribute(attr, value);
    //       console.log(`Set ${id} ${attr}=${value}`); // Debugging
    //     } else {
    //       console.warn(`Element with ID ${id} not found`);
    //     }
    //   }
    
    //   // Update DOM
    //   const headerEl = document.getElementById("detail_header_" + section_id);
    //   if (headerEl) headerEl.textContent = saved_detail_header;
    
    //   setAttr("detail_correct_overall_" + section_id, "data-percentage", saved_detail_correct_overall);
    //   setAttr("detail_correct_when_receiving_" + section_id, "data-percentage", saved_detail_correct_when_receiving);
    //   setAttr("detail_correct_with_no_" + section_id, "data-percentage", saved_detail_correct_with_no);
    
    //   const timerEl = document.getElementById("bc_timer-count-1_" + section_id);
    //   if (timerEl) timerEl.textContent = saved_detail_avg_time;
    
    //   setAttr("detail_correct_step1_" + section_id, "data-percentage", saved_detail_correct_step1);
    //   setAttr("detail_correct_step2a_" + section_id, "data-percentage", saved_detail_correct_step2a);
    //   setAttr("detail_correct_step2b_" + section_id, "data-percentage", saved_detail_correct_step2b);
    // }

    
    function setBack(section_id, section_main_id) {
      const section = document.getElementById(section_id);
      const sectionMain = document.getElementById(section_main_id);

      
      hideWithAnimation(section);
      showWithAnimation(sectionMain);
    }

    
    function removeDetails(){
        localStorage.setItem("detail_is_setted", false);
        localStorage.clear();
    }
    
    
    const tabs = document.querySelectorAll('.bc_tab');
    const contents = document.querySelectorAll('.bc_tab_content');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        // Remove active class from all tabs
        tabs.forEach(t => t.classList.remove('bc_active'));
        // Hide all content
        contents.forEach(c => c.classList.remove('bc_active'));

        // Add active to clicked tab
        tab.classList.add('bc_active');
        // Show corresponding content
        const target = tab.getAttribute('data-target');
        document.getElementById(target).classList.add('bc_active');
      });
    });
</script>