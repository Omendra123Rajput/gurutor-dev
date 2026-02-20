<style>
.cursor-pointer {
  cursor: pointer;
}

.dq-none {
  display: none !important;
}

.fade-section {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.fade-out {
  opacity: 0;
  transform: translateY(-20px); /* slide up */
}

.fade-in {
  opacity: 0;
  transform: translateY(-20); /* slide down */
}


.overview-section-wrapper .course-wrapper-content#overview {
    padding-top: 0px !important;
}

    .bc_main_tabs {
      display: flex;
      justify-content: flex-start;
      border-bottom: 2px solid #eee;
      background: white;
    }

    .bc_main_tab {
      /*flex: 1;*/
      text-align: center;
      padding: 20px 0;
      cursor: pointer;
      font-weight: 500;
      color: #002434;
      transition: all 0.3s ease;
      position: relative;
      margin-left: 20px;
      width: 140px;
      color: #adadad;
    }

    .bc_main_tab.active {
      color: #01087C;
      font-weight: bold;
    }

    .bc_main_tab.active::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0%;
      width: 100%;
      height: 3px;
      background-color: #01087C;
      transition: all 0.3s ease;
    }

    .bc_main_content {
      display: none;
      padding: 20px;
      animation: fadeIn 0.4s ease-in-out;
    }

    .bc_main_content.active {
      display: block;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>