<style>
  /* Progress Bar Styles */
  .bc6_progress-container {
    width: 95%;
    display: flex;
    height: 45px;
  }

  .bc6_label {
    font-weight: bold;
    margin-bottom: 0px;
    font-size: 18px;
    color: #002434;
    margin-right: 30px;
    width: 40%;
  }

  .bc6_progress-bar {
    position: relative;
    height: 8px;
    background-color: #B8B8B8;
    border-radius: 4px;
    overflow: hidden;
  }

  .bc6_progress-fill-yellow {
    height: 100%;
    width: 0;
    background-color: #FFBB1D;
    transition: width 2s ease-out;
  }

  .bc6_progress-fill-red {
    height: 100%;
    width: 0;
    background-color: #EB2E29;
    transition: width 2s ease-out;
  }

 .bc6_progress-fill {
    height: 100%;
    width: 0;
    background-color: #005AE2;
    transition: width 2s ease-out;
  }

  .bc6_footer {
    display: flex;
    text-align: center;
    margin: 15px;
  }

  .bc6_percentage-text {
    display: inline-block;
    font-weight: bold;
    color: #002434;
    font-size: 18px;
    margin-bottom: 0px;
  }

  .bc6_checkmark {
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
  .bc6_main-wrapper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    max-width: 100%;
    margin: 30px 5px;
  }

  .bc6_left {
    flex: 1;
  }
</style>

<!-- FLEX CONTAINER -->
<div class="bc6_main-wrapper">

  <!-- LEFT SIDE: PROGRESS BARS -->
  <div class="bc6_left">
    <!-- Progress Line 1 -->
    <div class="bc6_progress-container" id="DD_overview" data-percentage="93">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc6_label cursor-pointer" onclick="setDetails('cr_divisibility_primes', 'number_properties', 'Divisibility and Primes');">Divisibility and Primes</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc6_progress-bar">
              <div class="bc6_progress-fill cursor-pointer" onclick="setDetails('cr_divisibility_primes', 'number_properties', 'Divisibility and Primes');"></div>
            </div>
          </div>
      </div>
      <div class="bc6_footer">
        <span class="bc6_percentage-text" id="DD_overview_txt" >0%</span>
        <span class="bc6_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
    <!-- Progress Line 2 -->
    <div class="bc6_progress-container" id="DP_overview" data-percentage="96">
      <div style="display: flex;width: 100%;margin: 18px;margin-left: 0px;">
          <div class="bc6_label cursor-pointer" onclick="setDetails('cr_digits_decimals', 'number_properties', 'Digits and Decimals');">Digits and Decimals</div>
          <div style="width: 90%;margin-top: 8px;">
            <div class="bc6_progress-bar">
              <div class="bc6_progress-fill cursor-pointer" onclick="setDetails('cr_digits_decimals', 'number_properties', 'Digits and Decimals');"></div>
            </div>
          </div>
      </div>
      <div class="bc6_footer">
        <span class="bc6_percentage-text" id="DP_overview_txt">0%</span>
        <span class="bc6_checkmark">
          <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
        </span>
      </div>
    </div>
    
    
    
  </div>

</div>

<script>
window.addEventListener("load", () => {
    const containers5 = document.querySelectorAll('.bc6_progress-container');

    containers5.forEach(container => {
        const percentage = parseInt(container.getAttribute('data-percentage'), 10);
        const text = container.querySelector('.bc6_percentage-text');

        // Try all known fill classes
        const fill = container.querySelector(
            '.bc6_progress-fill, .bc6_progress-fill-yellow, .bc6_progress-fill-red'
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

