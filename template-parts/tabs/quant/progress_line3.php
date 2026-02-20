<style>
  /* Progress Bar Styles */
  .bc5_progress-container {
    width: 95%;
    display: flex;
    height: 45px;
  }

  .bc5_label {
    font-weight: bold;
    margin-bottom: 0px;
    font-size: 18px;
    color: #002434;
    margin-right: 30px;
    width: 40%;
  }

  .bc5_progress-bar {
    position: relative;
    height: 8px;
    background-color: #B8B8B8;
    border-radius: 4px;
    overflow: hidden;
  }

  .bc5_progress-fill-yellow {
    height: 100%;
    width: 0;
    background-color: #FFBB1D;
    transition: width 2s ease-out;
  }

  .bc5_progress-fill-red {
    height: 100%;
    width: 0;
    background-color: #EB2E29;
    transition: width 2s ease-out;
  }

 .bc5_progress-fill {
    height: 100%;
    width: 0;
    background-color: #005AE2;
    transition: width 2s ease-out;
  }

  .bc5_footer {
    display: flex;
    text-align: center;
    margin: 15px;
  }

  .bc5_percentage-text {
    display: inline-block;
    font-weight: bold;
    color: #002434;
    font-size: 18px;
    margin-bottom: 0px;
  }

  .bc5_checkmark {
    color: green;
    font-size: 1.2em;
    width: 24px;
    margin: auto;
    align-items: center;
    align-content: baseline;
    margin-left: 15px;
    margin-top: 0px;
  }

 
  /* FLEX CONTAINER FOR BOTH SECTIONS */
  .bc5_main-wrapper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    max-width: 100%;
    margin: 30px 5px;
  }

  .bc5_left {
    flex: 1;
  }
</style>

<!-- FLEX CONTAINER -->
<div class="bc5_main-wrapper">

  <!-- LEFT SIDE: PROGRESS BARS -->
  <div class="bc5_left">
    <!-- Progress Line 1 -->
    <div class="bc5_progress-container" id="ER_overview" data-percentage="93">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc5_label cursor-pointer" onclick="setDetails('cr_algebra', 'algebra', 'Exponents and Roots');">Exponents and Roots</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc5_progress-bar">
              <div class="bc5_progress-fill cursor-pointer" onclick="setDetails('cr_algebra', 'algebra', 'Exponents and Roots');"></div>
            </div>
          </div>
      </div>
      <div class="bc5_footer">
        <span class="bc5_percentage-text" id="ER_overview_txt">2%</span>
        <span class="bc5_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 2 -->
    <div class="bc5_progress-container" id="QD_overview" data-percentage="96">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc5_label cursor-pointer" onclick="setDetails('cr_quadratics', 'algebra', 'Quadratics');">Quadratics</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc5_progress-bar">
              <div class="bc5_progress-fill cursor-pointer" onclick="setDetails('cr_quadratics', 'algebra', 'Quadratics');"></div>
            </div>
          </div>
      </div>
      <div class="bc5_footer">
        <span class="bc5_percentage-text" id="QD_overview_txt">2%</span>
        <span class="bc5_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    

    <!-- Progress Line 3 -->
    <div class="bc5_progress-container" id="INEQ_overview" data-percentage="51">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc5_label cursor-pointer" onclick="setDetails('cr_main_quadratics', 'algebra', 'Main Quadratics');">Main Quadratics</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc5_progress-bar">
              <div class="bc5_progress-fill-yellow cursor-pointer" onclick="setDetails('cr_main_quadratics', 'algebra', 'Main Quadratics');"></div>
            </div>
          </div>
      </div>
      <div class="bc5_footer">
        <span class="bc5_percentage-text" id="INEQ_overview_txt">2%</span>
        <span class="bc5_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/warning_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 4 -->
    <div class="bc5_progress-container" id="FFS_overview" data-percentage="96">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc5_label cursor-pointer" onclick="setDetails('cr_ffs', 'algebra', 'FFS');">FFS</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc5_progress-bar">
              <div class="bc5_progress-fill cursor-pointer" onclick="setDetails('cr_ffs', 'algebra', 'FFS');"></div>
            </div>
          </div>
      </div>
      <div class="bc5_footer">
        <span class="bc5_percentage-text" id="FFS_overview_txt">2%</span>
        <span class="bc5_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>



    <!-- Progress Line 5 -->
    <div class="bc5_progress-container" id="LE_overview" data-percentage="33">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc5_label cursor-pointer" onclick="setDetails('cr_linear', 'algebra', 'Linear Equations', '80', '90', '92', '234', '88', '33', '44');">Linear Equations</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc5_progress-bar">
              <div class="bc5_progress-fill-red cursor-pointer" onclick="setDetails('cr_linear', 'algebra', 'Linear Equations', '80', '90', '92', '234', '88', '33', '44');"></div>
            </div>
          </div>
      </div>
      <div class="bc5_footer">
        <span class="bc5_percentage-text" id="LE_overview_txt">2%</span>
        <span class="bc5_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/error_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
  </div>

</div>

<script>
  // Progress Bar Animation - works for all fill colors
window.addEventListener("load", () => {
    const containers5 = document.querySelectorAll('.bc5_progress-container');

    containers5.forEach(container => {
        const percentage = parseInt(container.getAttribute('data-percentage'), 10);
        const text = container.querySelector('.bc5_percentage-text');

        // Try all known fill classes
        const fill = container.querySelector(
            '.bc5_progress-fill, .bc5_progress-fill-yellow, .bc5_progress-fill-red'
        );

        if (!fill) return;

        fill.style.width = `${percentage}%`;

        let current = 0;
        const interval = setInterval(() => {
            if (current >= percentage) {
                clearInterval(interval);
                text.textContent = `${percentage}%`;
            } else {
                current++;
                text.textContent = `${current}%`;
            }
        }, 20);
    });
});

</script>

