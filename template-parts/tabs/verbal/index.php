 

  <div class="nav">
    <button type="button" class="button selected" onclick="document.querySelector('#cr-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#cr-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('cr_by_question_type').classList.remove('hidden')"><span class="text">BY QUESTION TYPE</span></button>
    <button type="button" class="button" onclick="document.querySelector('#cr-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#cr-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('cr_by_argument_type').classList.remove('hidden')"><span class="text">BY ARGUMENT TYPE</span></button>
    <button type="button" class="button" onclick="document.querySelector('#cr-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#cr-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('cr_by_process').classList.remove('hidden')"><span class="text">BY PROCESS</span></button>
  </div>
  <div id="cr_by_question_type" class="statistics-wrapper">
        <?php include get_theme_file_path('template-parts/tabs/verbal/question_type.php'); ?>
  </div>
  <div id="cr_by_argument_type" class="hidden statistics-wrapper">
        <?php include get_theme_file_path('template-parts/tabs/verbal/argument_type.php'); ?>
  </div>
  <div id="cr_by_process" class="hidden statistics-wrapper">
        <?php include get_theme_file_path('template-parts/tabs/verbal/process.php'); ?>
  </div>
  
  <script src="/wp-content/themes/statistics/apexcharts-bundle/dist/apexcharts.js"></script>
  <link rel="stylesheet" href="/wp-content/themes/statistics/apexcharts-bundle/dist/apexcharts.css">
  

 <?php include get_theme_file_path('template-parts/includes/style.php'); ?>
 <?php include get_theme_file_path('template-parts/includes/scripts.php'); ?>
