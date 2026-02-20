 <style>

    .bc2_container {
      display: flex;
      justify-content: space-between;
    }

    .bc2_card {
      background: #F5F5F5;
      border-radius: 23px;
      padding: 32px 24px;
      width: 30%;
      text-align: center;
    }

    .bc2_percentage {
      font-size: 42px;
      color: #002434;
      margin: 0 0 12px;
      font-weight: bold;
    }

    .bc2_bar_bg {
      width: 100%;
      height: 8px;
      background-color: #B8B8B8;
      border-radius: 4px;
      overflow: hidden;
      margin-bottom: 16px;
    }

    .bc2_bar_fill {
      height: 100%;
      width: 0%;
      border-radius: 4px;
      transition: width 1.2s ease-out;
    }

    .bc2_red { background-color: #EB2E29; }
    .bc2_blue { background-color: #005AE2; }
    .bc2_orange { background-color: #FFBB1D; }

    .bc2_label {
      font-size: 16px;
      color: #002434;
      margin: 0;
      font-weight: 400;
    }

    .bc2_step {
      font-size: 16px;
      color: #002434;
      font-weight: 700;
    }
  </style>
  
  
 <div class="bc2_container">
    <div class="bc2_card" id="detail_correct_step1_<?php echo $section_id; ?>" data-percentage="28" data-color="red">
      <div class="bc2_percentage" id="detail_correct_step1_<?php echo $section_id; ?>_txt" >2%</div>
      <div class="bc2_bar_bg">
        <div class="bc2_bar_fill bc2_red"></div>
      </div>
      <p class="bc2_label">CORRECT</p>
      <p class="bc2_step">STEP 1</p>
    </div>

    <div class="bc2_card" id="detail_correct_step2a_<?php echo $section_id; ?>" data-percentage="95" data-color="blue">
      <div class="bc2_percentage" id="detail_correct_step2a_<?php echo $section_id; ?>_txt">2%</div>
      <div class="bc2_bar_bg">
        <div class="bc2_bar_fill bc2_blue"></div>
      </div>
      <p class="bc2_label">CORRECT</p>
      <p class="bc2_step">STEP 2A</p>
    </div>

    <div class="bc2_card" id="detail_correct_step2b_<?php echo $section_id; ?>" data-percentage="77" data-color="orange">
      <div class="bc2_percentage" id="detail_correct_step2b_<?php echo $section_id; ?>_txt">2%</div>
      <div class="bc2_bar_bg">
        <div class="bc2_bar_fill bc2_orange"></div>
      </div>
      <p class="bc2_label">CORRECT</p>
      <p class="bc2_step">STEP 2B</p>
    </div>
  </div>

  <script>
    function animateProgress(card) {
      const percent = parseInt(card.dataset.percentage);
      const fill = card.querySelector('.bc2_bar_fill');
      const number = card.querySelector('.bc2_percentage');

      let current = 0;
      const interval = setInterval(() => {
        if (current >= percent) {
          clearInterval(interval);
        } else {
          current++;
          number.textContent = `${current}%`;
          fill.style.width = `${current}%`;
        }
      }, 15); // adjust speed
    }

    window.addEventListener("load", () => {
      document.querySelectorAll('.bc2_card').forEach(card => {
        animateProgress(card);
      });
    });
  </script>
  
  
  