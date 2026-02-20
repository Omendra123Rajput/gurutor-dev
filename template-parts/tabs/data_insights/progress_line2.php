<style>
  /* Progress Bar Styles */
  .bc4_progress-container {
    width: 95%;
    display: flex;
    height: 45px;
  }

  .bc4_label {
    font-weight: bold;
    margin-bottom: 0px;
    font-size: 18px;
    color: #002434;
    margin-right: 30px;
    width: 40%;
  }

  .bc4_progress-bar {
    position: relative;
    height: 8px;
    background-color: #B8B8B8;
    border-radius: 4px;
    overflow: hidden;
  }

  .bc4_progress-fill-yellow {
    height: 100%;
    width: 0;
    background-color: #FFBB1D;
    transition: width 2s ease-out;
  }

  .bc4_progress-fill-red {
    height: 100%;
    width: 0;
    background-color: #EB2E29;
    transition: width 2s ease-out;
  }

 .bc4_progress-fill {
    height: 100%;
    width: 0;
    background-color: #005AE2;
    transition: width 2s ease-out;
  }

  .bc4_footer {
    display: flex;
    text-align: center;
    margin: 15px;
  }

  .bc4_percentage-text {
    display: inline-block;
    font-weight: bold;
    color: #002434;
    font-size: 18px;
    margin-bottom: 0px;
  }

  .bc4_checkmark {
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
  .bc4_main-wrapper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    max-width: 100%;
    margin: 30px 5px;
  }

  .bc4_left {
    flex: 1;
  }
</style>

<!-- FLEX CONTAINER -->
<div class="bc4_main-wrapper">

  <!-- LEFT SIDE: PROGRESS BARS -->
  <div class="bc4_left">
    <!-- Progress Line 1 -->
    <div class="bc4_progress-container" data-percentage="93">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc4_label">Data Sufficiency</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc4_progress-bar">
              <div class="bc4_progress-fill"></div>
            </div>
          </div>
      </div>
      <div class="bc4_footer">
        <span class="bc4_percentage-text">0%</span>
        <span class="bc4_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 2 -->
    <div class="bc4_progress-container" data-percentage="96">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc4_label">Multi-Source Reasoning</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc4_progress-bar">
              <div class="bc4_progress-fill"></div>
            </div>
          </div>
      </div>
      <div class="bc4_footer">
        <span class="bc4_percentage-text">0%</span>
        <span class="bc4_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    

    <!-- Progress Line 3 -->
    <div class="bc4_progress-container" data-percentage="51">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc4_label">Two-Part Analysis</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc4_progress-bar">
              <div class="bc4_progress-fill-yellow"></div>
            </div>
          </div>
      </div>
      <div class="bc4_footer">
        <span class="bc4_percentage-text">0%</span>
        <span class="bc4_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/warning_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 4 -->
    <div class="bc4_progress-container" data-percentage="96">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc4_label">Table Analysis</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc4_progress-bar">
              <div class="bc4_progress-fill"></div>
            </div>
          </div>
      </div>
      <div class="bc4_footer">
        <span class="bc4_percentage-text">0%</span>
        <span class="bc4_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>



    <!-- Progress Line 5 -->
    <div class="bc4_progress-container" data-percentage="33">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc4_label">Graphic Interpretation</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc4_progress-bar">
              <div class="bc4_progress-fill-red"></div>
            </div>
          </div>
      </div>
      <div class="bc4_footer">
        <span class="bc4_percentage-text">0%</span>
        <span class="bc4_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/error_tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
  </div>

</div>

<script>
  // Progress Bar Animation - works for all fill colors
  const containers = document.querySelectorAll('.bc4_progress-container');

  containers.forEach(container => {
    const percentage = parseInt(container.getAttribute('data-percentage'), 10);
    const text = container.querySelector('.bc4_percentage-text');

    // Try all known fill classes
    const fill = container.querySelector(
      '.bc4_progress-fill, .bc4_progress-fill-yellow, .bc4_progress-fill-red'
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
</script>

