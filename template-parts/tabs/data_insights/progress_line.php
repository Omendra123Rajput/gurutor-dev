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
      <?php include get_theme_file_path('template-parts/tabs/data_insights/header.php'); ?>
      <!-- Progress Line 1 -->
      <div class="bc_progress-container" data-percentage="89" style="margin-top: 20px;">
        <div style="width: 100%;margin-top: 8px;">
          <div class="bc_label">Correct</div>
          <div class="bc_progress-bar">
            <div class="bc_progress-fill"></div>
          </div>
        </div>
        <div class="bc_footer">
          <span class="bc_percentage-text">0%</span>
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
        <div class="bc_timer-time" id="bc_timer-count-2">0</div>
        <div class="bc_timer-label">Average Time</div>
      </div>
    </div>

  </div>

  <!-- JavaScript -->
  <script>
      
        // Animate counter 2
        let count2 = 0;
        const target2 = 106;
        const counter2 = document.getElementById('bc_timer-count-2');
        const animate2 = () => {
          if (count2 < target2) {
            count2++;
            counter2.textContent = `${count2} min`;
            setTimeout(animate2, 20);
          }
        };
        animate2();
       
  window.onload = () => {
    // Progress Bar Animation
    const containers = document.querySelectorAll('.bc_progress-container');

    containers.forEach(container => {
      const percentage = parseInt(container.getAttribute('data-percentage'), 10);
      const fill = container.querySelector('.bc_progress-fill');
      const text = container.querySelector('.bc_percentage-text');

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


  };
</script>
