<style>
  /* Progress Bar Styles */
  .bc7_progress-container {
    width: 95%;
    display: flex;
    height: 45px;
  }

  .bc7_label {
    font-weight: bold;
    margin-bottom: 0px;
    font-size: 18px;
    color: #002434;
    margin-right: 30px;
    width: 40%;
  }

  .bc7_progress-bar {
    position: relative;
    height: 8px;
    background-color: #B8B8B8;
    border-radius: 4px;
    overflow: hidden;
  }

  .bc7_progress-fill-yellow {
    height: 100%;
    width: 0;
    background-color: #FFBB1D;
    transition: width 2s ease-out;
  }

  .bc7_progress-fill-red {
    height: 100%;
    width: 0;
    background-color: #EB2E29;
    transition: width 2s ease-out;
  }

 .bc7_progress-fill {
    height: 100%;
    width: 0;
    background-color: #005AE2;
    transition: width 2s ease-out;
  }

  .bc7_footer {
    display: flex;
    text-align: center;
    margin: 15px;
  }

  .bc7_percentage-text {
    display: inline-block;
    font-weight: bold;
    color: #002434;
    font-size: 18px;
    margin-bottom: 0px;
  }

  .bc7_checkmark {
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
  .bc7_main-wrapper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    max-width: 100%;
    margin: 30px 5px;
  }

  .bc7_left {
    flex: 1;
  }
</style>

<!-- FLEX CONTAINER -->
<div class="bc7_main-wrapper">

  <!-- LEFT SIDE: PROGRESS BARS -->
  <div class="bc7_left">
    <!-- Progress Line 1 -->
    <div class="bc7_progress-container" id="TRN_overview" data-percentage="89">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc7_label cursor-pointer" onclick="setDetails('cr_translations', 'word_problems', 'Translations');">Translations</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc7_progress-bar">
              <div class="bc7_progress-fill cursor-pointer" onclick="setDetails('cr_translations', 'word_problems', 'Translations');"></div>
            </div>
          </div>
      </div>
      <div class="bc7_footer">
        <span class="bc7_percentage-text" id="TRN_overview_txt">0%</span>
        <span class="bc7_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 2 -->
    <div class="bc7_progress-container" id="RTW_overview" data-percentage="96">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc7_label cursor-pointer" onclick="setDetails('cr_rates', 'word_problems', 'Rates');">Rates</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc7_progress-bar">
              <div class="bc7_progress-fill cursor-pointer" onclick="setDetails('cr_rates', 'word_problems', 'Rates');"></div>
            </div>
          </div>
      </div>
      <div class="bc7_footer">
        <span class="bc7_percentage-text" id="RTW_overview_txt">0%</span>
        <span class="bc7_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    

    <!-- Progress Line 3 -->
    <div class="bc7_progress-container" id="OS_overview" data-percentage="47">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc7_label cursor-pointer" onclick="setDetails('cr_overlapping', 'word_problems', 'Overlapping Sets');">Overlapping Sets</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc7_progress-bar">
              <div class="bc7_progress-fill-yellow cursor-pointer" onclick="setDetails('cr_overlapping', 'word_problems', 'Overlapping Sets');"></div>
            </div>
          </div>
      </div>
      <div class="bc7_footer">
        <span class="bc7_percentage-text" id="OS_overview_txt">0%</span>
        <span class="bc7_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/warning_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 4 -->
    <div class="bc7_progress-container" id="STS_overview" data-percentage="95">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc7_label cursor-pointer" onclick="setDetails('cr_statistics', 'word_problems', 'Statistics');">Statistics</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc7_progress-bar">
              <div class="bc7_progress-fill cursor-pointer" onclick="setDetails('cr_statistics', 'word_problems', 'Statistics');"></div>
            </div>
          </div>
      </div>
      <div class="bc7_footer">
        <span class="bc7_percentage-text" id="STS_overview_txt">0%</span>
        <span class="bc7_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>



    <!-- Progress Line 5 -->
    <div class="bc7_progress-container" id="ESS_overview" data-percentage="49">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc7_label cursor-pointer" onclick="setDetails('cr_evenly', 'word_problems', 'Evenly-Spaced Sets');">Evenly-Spaced Sets</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc7_progress-bar">
              <div class="bc7_progress-fill-yellow cursor-pointer" onclick="setDetails('cr_evenly', 'word_problems', 'Evenly-Spaced Sets');"></div>
            </div>
          </div>
      </div>
      <div class="bc7_footer">
        <span class="bc7_percentage-text" id="ESS_overview_txt">0%</span>
        <span class="bc7_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/warning_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    <div class="bc7_progress-container" id="PRB_overview" data-percentage="33">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc7_label cursor-pointer" onclick="setDetails('cr_probability', 'word_problems', 'Probability');">Probability</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc7_progress-bar">
              <div class="bc7_progress-fill-red cursor-pointer" onclick="setDetails('cr_probability', 'word_problems', 'Probability');"></div>
            </div>
          </div>
      </div>
      <div class="bc7_footer">
        <span class="bc7_percentage-text" id="PRB_overview_txt">0%</span>
        <span class="bc7_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/error_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
     <div class="bc7_progress-container" id="CMB_overview" data-percentage="100">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc7_label cursor-pointer" onclick="setDetails('cr_combinatorics', 'word_problems', 'Combinatorics');">Combinatorics</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc7_progress-bar">
              <div class="bc7_progress-fill cursor-pointer" onclick="setDetails('cr_combinatorics', 'word_problems', 'Combinatorics');"></div>
            </div>
          </div>
      </div>
      <div class="bc7_footer">
        <span class="bc7_percentage-text" id="CMB_overview_txt">0%</span>
        <span class="bc7_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
  </div>

</div>

<script>
window.addEventListener("load", () => {
    const containers7 = document.querySelectorAll('.bc7_progress-container');

    containers7.forEach(container => {
        const percentage = parseInt(container.getAttribute('data-percentage'), 10);
        const text = container.querySelector('.bc7_percentage-text');

        // Try all known fill classes
        const fill = container.querySelector(
            '.bc7_progress-fill, .bc7_progress-fill-yellow, .bc7_progress-fill-red'
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

