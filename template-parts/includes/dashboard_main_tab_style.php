 <style>
.box {
    width: 1073px;
    background: #FFFFFF 0% 0% no-repeat padding-box;
    box-shadow: 3px 8px 22px #0000000D;
    border-radius: 23px;
    opacity: 1;
    padding: 38px;
}

    .bc_tabs {
      display: flex;
      gap: 30px;
      padding-bottom: 10px;
    }

    .bc_tab {
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      color: #7a8b9f;
      background-color: transparent;
      cursor: pointer;
      transition: background-color 0.2s, color 0.2s;
    }

    .bc_tab.bc_active {
      background-color: #E4EBF7;
      color: #000;
    }

    .bc_tab_content {
      display: none;
      margin-top: 20px;
      font-size: 14px;
      color: #333;
    }

    .bc_tab_content.bc_active {
      display: block;
    }
  </style>