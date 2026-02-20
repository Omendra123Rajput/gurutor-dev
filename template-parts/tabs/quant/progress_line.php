   <style>
    /* Progress Bar Styles */
    .bc_progress-container {
      width: 95%;
      display: flex;
      height: 60px;
    }

    .bc_label {
      font-weight: bold;
      margin-bottom: 5px;
      font-size: 15px;
      color: #002434;
    }

    .bc_progress-bar {
      position: relative;
      height: 8px;
      background-color: #B8B8B8;
      border-radius: 4px;
      overflow: hidden;
    }

    .bc_progress-fill {
      height: 100%;
      width: 0;
      background-color: #005AE2;
      transition: width 2s ease-out;
    }

    .bc_footer {
      display: flex;
      text-align: center;
      margin: 15px;
    }

    .bc_percentage-text {
      display: inline-block;
      font-weight: bold;
      color: #002434;
      font-size: 28px;
      margin-bottom: 8px;
    }

    .bc_checkmark {
      color: green;
      font-size: 1.2em;
      width: 24px;
      margin: auto;
      align-items: center;
      align-content: baseline;
      margin-left: 15px;
      margin-top: 10px;
    }

    /* TIMER PANEL STYLES */
    .bc_timer-box {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      width: 210px;
      height: 160px;
      margin: 20px;
    }

    .bc_timer-icon img {
        width: 100%;
        height: auto;
        margin-bottom: 10px;
        background-color: rgba(255, 187, 29, 0.2);
        border-radius: 100%;
    }

    .bc_timer-time {
      font-size: 36px;
      font-weight: bold;
      color: #002434;
      margin-bottom: 4px;
    }

    .bc_timer-label {
      font-size: 18px;
      color: #002434;
      text-align: center;
    }

    /* FLEX CONTAINER FOR BOTH SECTIONS */
    .bc_main-wrapper {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      max-width: 100%;
      margin: 30px 5px;
    }

    .bc_left {
      flex: 1;
    }
  </style>
  
 

  
 <!-- FLEX CONTAINER -->
  <div class="bc_main-wrapper">
    
    <!-- LEFT SIDE: PROGRESS BARS -->
    <div class="bc_left">
      
      <!-- Progress Line 1 -->
      <div class="bc_progress-container" id="detail_correct_overall_<?php echo $section_id; ?>" data-percentage="10">
        <div style="width: 100%;margin-top: 8px;">
          <div class="bc_label">Correct Overall</div>
          <div class="bc_progress-bar">
            <div class="bc_progress-fill"></div>
          </div>
        </div>
        <div class="bc_footer">
          <span class="bc_percentage-text" id="detail_correct_overall_<?php echo $section_id; ?>_txt">2%</span>
          <span class="bc_checkmark">
            <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
          </span>
        </div>
      </div>

      <!-- Progress Line 2 -->
      <div class="bc_progress-container" id="detail_correct_when_receiving_<?php echo $section_id; ?>" data-percentage="12">
        <div style="width: 100%;margin-top: 8px;">
          <div class="bc_label">Correct when Receiving Gurutor Support</div>
          <div class="bc_progress-bar">
            <div class="bc_progress-fill"></div>
          </div>
        </div>
        <div class="bc_footer">
          <span class="bc_percentage-text" id="detail_correct_when_receiving_<?php echo $section_id; ?>_txt">1%</span>
          <span class="bc_checkmark">
            <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
          </span>
        </div>
      </div>

      <!-- Progress Line 3 -->
      <div class="bc_progress-container" id="detail_correct_with_no_<?php echo $section_id; ?>" data-percentage="10">
        <div style="width: 100%;margin-top: 8px;">
          <div class="bc_label">Correct with No Support</div>
          <div class="bc_progress-bar">
            <div class="bc_progress-fill"></div>
          </div>
        </div>
        <div class="bc_footer">
          <span class="bc_percentage-text" id="detail_correct_with_no_<?php echo $section_id; ?>_txt">3%</span>
          <span class="bc_checkmark">
            <img src="<?php echo get_theme_file_uri('template-parts/icons/tick.svg'); ?>"/>
          </span>
        </div>
      </div>
        
    </div>

    <!-- RIGHT SIDE: TIMER PANEL -->
    <div class="bc_timer-box">
      <div class="bc_timer-icon">
        <img src="<?php echo get_theme_file_uri('template-parts/icons/timer.svg'); ?>" alt="Timer Icon" />
      </div>
      <div>
        <div class="bc_timer-time" id="bc_timer-count-1_<?php echo $section_id; ?>">
           1 min
        </div>
        <div class="bc_timer-label">Average Time <?php echo $data; ?></div>
      </div>
    </div>

  </div>


  
  <script> 
          // Animate counter 1
       
    // Progress bars animation
    window.onload = () => {
      
      const containers = document.querySelectorAll('.bc_progress-container');

      containers.forEach(container => {
        const percentage = parseInt(container.getAttribute('data-percentage'), 10);
        const fill = container.querySelector('.bc_progress-fill');
        const text = container.querySelector('.bc_percentage-text');

        fill.style.width = `${percentage}%`;

        let current = 0;
        // const interval = setInterval(() => {
        //   if (current >= percentage) {
        //     clearInterval(interval);
        //     text.textContent = `${percentage}%`;
        //   } else {
        //     current++;
        //     text.textContent = `${current}%`;
        //   }
        // }, 20);
      });

 

    };
    
    
  </script> 