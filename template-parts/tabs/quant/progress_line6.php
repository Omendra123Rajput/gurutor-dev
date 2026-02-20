<style>
  /* Progress Bar Styles */
  .bc8_progress-container {
    width: 95%;
    display: flex;
    height: 45px;
  }

  .bc8_label {
    font-weight: bold;
    margin-bottom: 0px;
    font-size: 18px;
    color: #002434;
    margin-right: 30px;
    width: 40%;
  }

  .bc8_progress-bar {
    position: relative;
    height: 8px;
    background-color: #B8B8B8;
    border-radius: 4px;
    overflow: hidden;
  }

  .bc8_progress-fill-yellow {
    height: 100%;
    width: 0;
    background-color: #FFBB1D;
    transition: width 2s ease-out;
  }

  .bc8_progress-fill-red {
    height: 100%;
    width: 0;
    background-color: #EB2E29;
    transition: width 2s ease-out;
  }

 .bc8_progress-fill {
    height: 100%;
    width: 0;
    background-color: #005AE2;
    transition: width 2s ease-out;
  }

  .bc8_footer {
    display: flex;
    text-align: center;
    margin: 15px;
  }

  .bc8_percentage-text {
    display: inline-block;
    font-weight: bold;
    color: #002434;
    font-size: 18px;
    margin-bottom: 0px;
  }

  .bc8_checkmark {
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
  .bc8_main-wrapper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    max-width: 100%;
    margin: 30px 5px;
  }

  .bc8_left {
    flex: 1;
  }
</style>

<!-- FLEX CONTAINER -->
<div class="bc8_main-wrapper">

  <!-- LEFT SIDE: PROGRESS BARS -->
  <div class="bc8_left">
    <!-- Progress Line 1 -->
    <div class="bc8_progress-container" id="FR_overview" data-percentage="89">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc8_label cursor-pointer" onclick="setDetails('cr_fractions', 'fractions', 'Fractions');">Fractions</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc8_progress-bar">
              <div class="bc8_progress-fill cursor-pointer" onclick="setDetails('cr_fractions', 'fractions', 'Fractions');"></div>
            </div>
          </div>
      </div>
      <div class="bc8_footer">
        <span class="bc8_percentage-text" id="FR_overview_txt">0%</span>
        <span class="bc8_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 2 -->
    <div class="bc8_progress-container" id="PCT_overview" data-percentage="96">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc8_label cursor-pointer" onclick="setDetails('cr_percentages', 'fractions', 'Percentages');">Percentages</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc8_progress-bar">
              <div class="bc8_progress-fill cursor-pointer" onclick="setDetails('cr_percentages', 'fractions', 'Percentages');"></div>
            </div>
          </div>
      </div>
      <div class="bc8_footer">
        <span class="bc8_percentage-text" id="PCT_overview_txt">0%</span>
        <span class="bc8_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    

    <!-- Progress Line 3 -->
    <div class="bc8_progress-container" id="RA_overview" data-percentage="47">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc8_label cursor-pointer" onclick="setDetails('cr_ratios', 'fractions', 'Ratios');">Ratios</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc8_progress-bar">
              <div class="bc8_progress-fill-yellow cursor-pointer" onclick="setDetails('cr_ratios', 'fractions', 'Ratios');"></div>
            </div>
          </div>
      </div>
      <div class="bc8_footer">
        <span class="bc8_percentage-text" id="RA_overview_txt">0%</span>
        <span class="bc8_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/warning_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 4 -->
    <div class="bc8_progress-container" id="FPRC_overview" data-percentage="95">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc8_label cursor-pointer" onclick="setDetails('cr_fpr', 'fractions', 'FPR Connections');">FPR Connections</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc8_progress-bar">
              <div class="bc8_progress-fill cursor-pointer" onclick="setDetails('cr_fpr', 'fractions', 'FPR Connections');"></div>
            </div>
          </div>
      </div>
      <div class="bc8_footer">
        <span class="bc8_percentage-text" id="FPRC_overview_txt">0%</span>
        <span class="bc8_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>


    
    
  </div>

</div>

<script>
window.addEventListener("load", () => {
    const containers8 = document.querySelectorAll('.bc8_progress-container');

    containers8.forEach(container => {
        const percentage = parseInt(container.getAttribute('data-percentage'), 10);
        const text = container.querySelector('.bc8_percentage-text');

        // Try all known fill classes
        const fill = container.querySelector(
            '.bc8_progress-fill, .bc8_progress-fill-yellow, .bc8_progress-fill-red'
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

