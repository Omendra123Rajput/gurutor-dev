<div id="rc-box" class="box">
  <div class="nav">
    <button type="button" class="button selected" onclick="document.querySelector('#rc-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#rc-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('by_question_type').classList.remove('hidden')"><span class="text">BY QUESTION TYPE</span></button>
    <button type="button" class="button" onclick="document.querySelector('#rc-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#rc-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('by_process').classList.remove('hidden')"><span class="text">BY PROCESS</span></button>
  </div>
  <div id="by_question_type" class="statistics-wrapper">
    <div class="statistic">
      <div id="sd-chart"></div>
      <div class="label">
        <p class="header">Specific Detail</p>
        <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="sd-chart_green">
          <g id="checkmark" transform="translate(-806.425 -2424.426)">
            <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
              <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
            </g>
            <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
          </g>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="sd-chart_yellow">
          <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="sd-chart_red">
          <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
            <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
            <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
            <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
          </g>
        </svg>
      </div>
    </div>
    <div class="statistic">
      <div id="in-chart"></div>
      <div class="label">
        <p class="header">Inference</p>
        <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="in-chart_green">
          <g id="checkmark" transform="translate(-806.425 -2424.426)">
            <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
              <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
            </g>
            <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
          </g>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="in-chart_yellow">
          <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="in-chart_red">
          <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
            <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
            <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
            <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
          </g>
        </svg>
      </div>
    </div>
    <div class="statistic">
      <div id="ar-chart"></div>
      <div class="label">
        <p class="header">Author’s Reasoning</p>
        <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="ar-chart_green">
          <g id="checkmark" transform="translate(-806.425 -2424.426)">
            <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
              <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
            </g>
            <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
          </g>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="ar-chart_yellow">
          <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="ar-chart_red">
          <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
            <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
            <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
            <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
          </g>
        </svg>
      </div>
    </div>
    <div class="statistic">
      <div id="mi-chart"></div>
      <div class="label">
        <p class="header">Main Idea</p>
        <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="mi-chart_green">
          <g id="checkmark" transform="translate(-806.425 -2424.426)">
            <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
              <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
            </g>
            <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
          </g>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="mi-chart_yellow">
          <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="mi-chart_red">
          <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
            <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
            <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
            <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
          </g>
        </svg>
      </div>
    </div>
    <div class="statistic">
      <div id="cr-chart"></div>
      <div class="label">
        <p class="header">CR-Style</p>
        <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="cr-chart_green">
          <g id="checkmark" transform="translate(-806.425 -2424.426)">
            <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
              <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
            </g>
            <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
          </g>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="cr-chart_yellow">
          <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="cr-chart_red">
          <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
            <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
            <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
            <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
          </g>
        </svg>
      </div>
    </div>
  </div>
  <div id="by_process" class="hidden statistics-wrapper">
    <div class="statistic">
      <table>
        <tr>
          <td class="label">Identify Question Type</td>
          <td>
          <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="valueID_green">
          <g id="checkmark" transform="translate(-806.425 -2424.426)">
            <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
              <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
            </g>
            <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
          </g>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="valueID_yellow">
          <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="valueID_red">
          <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
            <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
            <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
            <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
          </g>
        </svg>
          </td>
          <td><input type="range" id="valueRangeID" name="points" min="0" max="100" value="93" disabled></td>
          <td class="percentage" id="valueID">0%</td>
        </tr>
        <tr>
          <td class="label">Find Passage Support</td>
          <td>
          <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="valueSupport_green">
          <g id="checkmark" transform="translate(-806.425 -2424.426)">
            <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
              <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
            </g>
            <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
          </g>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="valueSupport_yellow">
          <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="valueSupport_red">
          <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
            <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
            <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
            <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
          </g>
        </svg>
          </td>
          <td><input type="range" id="valueRangeSupport" name="points" min="0" max="100" value="34" disabled></td>
          <td class="percentage low" id="valueSupport">0%</td>
        </tr>
      </table>
    </div>
  </div>
  <script src="/wp-content/themes/statistics/apexcharts-bundle/dist/apexcharts.js"></script>
  <link rel="stylesheet" href="/wp-content/themes/statistics/apexcharts-bundle/dist/apexcharts.css">
  <style>
    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: normal;
      font-weight: 300;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-300.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: italic;
      font-weight: 300;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-300italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: normal;
      font-weight: 400;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-regular.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: italic;
      font-weight: 400;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: normal;
      font-weight: 500;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-500.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: italic;
      font-weight: 500;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-500italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: normal;
      font-weight: 600;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-600.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: italic;
      font-weight: 600;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-600italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: normal;
      font-weight: 700;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-700.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: italic;
      font-weight: 700;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-700italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: normal;
      font-weight: 800;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-800.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Open Sans';
      font-style: italic;
      font-weight: 800;
      src: url('/wp-content/themes/statistics/fonts/open-sans-v40-latin-800italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 200;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-200.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 200;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-200italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 300;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-300.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 300;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-300italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 400;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-regular.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 400;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 500;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-500.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 500;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-500italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 600;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-600.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 600;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-600italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 700;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-700.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 700;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-700italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 800;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-800.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 800;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-800italic.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: normal;
      font-weight: 900;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-900.woff2') format('woff2');
    }

    @font-face {
      font-display: swap;
      font-family: 'Nunito Sans';
      font-style: italic;
      font-weight: 900;
      src: url('/wp-content/themes/statistics/fonts/nunito-sans-v15-latin-900italic.woff2') format('woff2');
    }

    * {
      box-sizing: border-box;
    }
    html, body {
      background: #F5F5F5 0% 0% no-repeat padding-box;
      margin: 0;
      padding: 0;
    }
    button {
      border: none;
      background: none;
      padding: 0;
      margin: 0;
      cursor: pointer;
      padding: 16px 24px;
    }
    button:hover {
      background-color: transparent;
    }
    p {
      margin: 0;
    }
    ul {
      list-style: none;
      margin: 0;
    }
    .hidden {
      display: none!important;
    }
    .box {
      width: 1073px;
      background: #FFFFFF 0% 0% no-repeat padding-box;
      box-shadow: 3px 8px 22px #0000000D;
      border-radius: 23px;
      opacity: 1;

      padding: 38px;
    }
    .nav {
      display: flex;
      gap: 16px;
      margin-bottom: 30px;
    }
    .button.selected {
      background: #FFF3DB 0% 0% no-repeat padding-box;
      border-radius: 12px;
      opacity: 1;
    }
    .button .text {
      text-align: left;
      font: normal normal 900 14px/19px Nunito Sans;
      letter-spacing: 1.05px;
      color: #002434;
      text-transform: uppercase;
      opacity: 1;
    }
    .button:not(.selected) .text {
      opacity: .4;
    }
    #rc-box .statistics-wrapper {
      display: flex;
    }
    #rc-box #by_question_type .statistic {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
    }
    #rc-box #by_question_type .label {
      text-align: center;
      color: #002434;
      opacity: 1;
    }
    #rc-box #by_question_type .label .header {
      text-align: center;
      font: normal normal bold 18px/28px 'Open Sans';
      letter-spacing: 0px;
      color: #002434;
      margin-bottom: 10px;
    }

    #rc-box #by_process {
      padding: 0 26px;
    }
    #rc-box #by_process .statistic {
      width: 100%;
    }
    #rc-box #by_process .statistic table {
      width: 100%;
      border: none;
    }
    #rc-box #by_process .statistic table tbody tr {
      height: 100px;
      border: none;
    }
    #rc-box #by_process .statistic table tbody tr td {
      height: 40px;
      width: calc(100% - 265px);
      border: none;
    }
    #rc-box #by_process .statistic table tbody tr td.label {
      font: normal normal bold 18px/28px 'Open Sans';
      letter-spacing: 0px;
      color: #002434;
      width: 265px;
    }
    #rc-box #by_process .statistic table tbody tr td.percentage {
      text-align: center;
      font: normal normal bold 28px/38px Open Sans;
      letter-spacing: -1.4px;
      color: #002434;
      opacity: 1;
    }
    #rc-box #by_process .statistic table tbody tr td.percentage.low {
      color: #EB2E29;
    }
    #rc-box #by_process .statistic table tbody tr td input {
      width: 100%;
    }


    #rc-box input[type="range"] {
      background-color: #FFF1D1;
      border-radius: 500px;
      height: 25px;
      width: 25px;
    }
    #rc-box input[type="range"]::-moz-range-progress {
      background: #FFBB1D;
      border-radius: 500px;
      height: 25px;
      width: 25px;
    }
    #rc-box input[type="range"]::-moz-range-thumb {
      appearance: none;
      -moz-appearance: none;
      -webkit-appearance: none;
      height: 0;
      width: 0;
      border: none;
    }

  </style>
  <script>
    var rcValues = {};
    rcValues.valueSD = ((window.lectoraModuleVars.VarStatsRCSDCorrect / window.lectoraModuleVars.VarStatsRCSDTotal) * 100) || 0;
    if (rcValues.valueSD && rcValues.valueSD >= 90) {
      document.getElementById('sd-chart_green').style.display = 'inline';
    } else if (rcValues.valueSD && rcValues.valueSD >= 70) {
      document.getElementById('sd-chart_yellow').style.display = 'inline';
    } else if (rcValues.valueSD && rcValues.valueSD < 70) {
      document.getElementById('sd-chart_red').style.display = 'inline';      
    }

    rcValues.valueINF = ((window.lectoraModuleVars.VarStatsRCINFCorrect / window.lectoraModuleVars.VarStatsRCINFTotal) * 100) || 0;
    if (rcValues.valueINF && rcValues.valueINF >= 90) {
      document.getElementById('in-chart_green').style.display = 'inline';
    } else if (rcValues.valueINF && rcValues.valueINF >= 70) {
      document.getElementById('in-chart_yellow').style.display = 'inline';
    } else if (rcValues.valueINF && rcValues.valueINF < 70) {
      document.getElementById('in-chart_red').style.display = 'inline';      
    }

    rcValues.valueAR = ((window.lectoraModuleVars.VarStatsRCARCorrect / window.lectoraModuleVars.VarStatsRCARTotal) * 100) || 0;
    if (rcValues.valueAR && rcValues.valueAR >= 90) {
      document.getElementById('ar-chart_green').style.display = 'inline';
    } else if (rcValues.valueAR && rcValues.valueAR >= 70) {
      document.getElementById('ar-chart_yellow').style.display = 'inline';
    } else if (rcValues.valueAR && rcValues.valueAR < 70) {
      document.getElementById('ar-chart_red').style.display = 'inline';      
    }

    rcValues.valueMI = ((window.lectoraModuleVars.VarStatsRCMICorrect / window.lectoraModuleVars.VarStatsRCMITotal) * 100) || 0;
    if (rcValues.valueMI && rcValues.valueMI >= 90) {
      document.getElementById('mi-chart_green').style.display = 'inline';
    } else if (rcValues.valueMI && rcValues.valueMI >= 70) {
      document.getElementById('mi-chart_yellow').style.display = 'inline';
    } else if (rcValues.valueMI && rcValues.valueMI < 70) {
      document.getElementById('mi-chart_red').style.display = 'inline';      
    }

    rcValues.valueCR = ((window.lectoraModuleVars.VarStatsRCCRCorrect / window.lectoraModuleVars.VarStatsRCCRTotal) * 100) || 0;
    if (rcValues.valueCR && rcValues.valueCR >= 90) {
      document.getElementById('cr-chart_green').style.display = 'inline';
    } else if (rcValues.valueCR && rcValues.valueCR >= 70) {
      document.getElementById('cr-chart_yellow').style.display = 'inline';
    } else if (rcValues.valueCR && rcValues.valueCR < 70) {
      document.getElementById('cr-chart_red').style.display = 'inline';      
    }

    rcValues.valueID = ((window.lectoraModuleVars.VarStatsRCIDCorrect / window.lectoraModuleVars.VarStatsRCQuestionsTotal) * 100) || 0;
    document.getElementById('valueID').textContent = Math.round(rcValues.valueID) + '%';
    document.getElementById('valueRangeID').value = Math.round(rcValues.valueID);
    if (rcValues.valueID && rcValues.valueID >= 90) {
      document.getElementById('valueID_green').style.display = 'inline';
    } else if (rcValues.valueID && rcValues.valueID >= 70) {
      document.getElementById('valueID_yellow').style.display = 'inline';
    } else if (rcValues.valueID && rcValues.valueID < 70) {
      document.getElementById('valueID_red').style.display = 'inline';      
    }

    rcValues.valueSupport = (window.lectoraModuleVars.VarStatsRCSupportCorrect / (window.lectoraModuleVars.VarStatsRCQuestionsTotal - window.lectoraModuleVars.VarStatsRCMITotal) * 100) || 0;
    document.getElementById('valueSupport').textContent = Math.round(rcValues.valueSupport) + '%';
    document.getElementById('valueRangeSupport').value = Math.round(rcValues.valueSupport);
    if (rcValues.valueSupport && rcValues.valueSupport >= 90) {
      document.getElementById('valueSupport_green').style.display = 'inline';
    } else if (rcValues.valueSupport && rcValues.valueSupport >= 70) {
      document.getElementById('valueSupport_yellow').style.display = 'inline';
    } else if (rcValues.valueSupport && rcValues.valueSupport < 70) {
      document.getElementById('valueSupport_red').style.display = 'inline';      
    }

    var sdChart = new ApexCharts(document.querySelector("#sd-chart"), {
      series: [Math.round(rcValues.valueSD)],
      chart: {
        height: 240,
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '70%',
          },
          track: {
            show: true,
            background: '#FFBB1D',
            strokeWidth: '100%',
            opacity: 0.2,
          },
          dataLabels: {
            show: true,
            name: {
              show: false,
            },
            value: {
              show: true,
              fontSize: '30px',
              fontWeight: 900,
              color: '#002434',
              offsetY: 12,
            }
          }
        },
      },
      colors: ['#FFBB1D'],
      legend: {
        show: false
      },
    });
    sdChart.render();
    var inChart = new ApexCharts(document.querySelector("#in-chart"), {
      series: [Math.round(rcValues.valueINF)],
      chart: {
        height: 240,
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '70%',
          },
          track: {
            show: true,
            background: '#FFBB1D',
            strokeWidth: '100%',
            opacity: 0.2,
          },
          dataLabels: {
            show: true,
            name: {
              show: false,
            },
            value: {
              show: true,
              fontSize: '30px',
              fontWeight: 900,
              color: '#002434',
              offsetY: 12,
            }
          }
        },
      },
      colors: ['#FFBB1D'],
      legend: {
        show: false
      },
    });
    inChart.render();
    var arChart = new ApexCharts(document.querySelector("#ar-chart"), {
      series: [Math.round(rcValues.valueAR)],
      chart: {
        height: 240,
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '70%',
          },
          track: {
            show: true,
            background: '#FFBB1D',
            strokeWidth: '100%',
            opacity: 0.2,
          },
          dataLabels: {
            show: true,
            name: {
              show: false,
            },
            value: {
              show: true,
              fontSize: '30px',
              fontWeight: 900,
              color: '#002434',
              offsetY: 12,
            }
          }
        },
      },
      colors: ['#FFBB1D'],
      legend: {
        show: false
      },
    });
    arChart.render();
    var miChart = new ApexCharts(document.querySelector("#mi-chart"), {
      series: [Math.round(rcValues.valueMI)],
      chart: {
        height: 240,
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '70%',
          },
          track: {
            show: true,
            background: '#FFBB1D',
            strokeWidth: '100%',
            opacity: 0.2,
          },
          dataLabels: {
            show: true,
            name: {
              show: false,
            },
            value: {
              show: true,
              fontSize: '30px',
              fontWeight: 900,
              color: '#002434',
              offsetY: 12,
            }
          }
        },
      },
      colors: ['#FFBB1D'],
      legend: {
        show: false
      },
    });
    miChart.render();
    var crChart = new ApexCharts(document.querySelector("#cr-chart"), {
      series: [Math.round(rcValues.valueCR)],
      chart: {
        height: 240,
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '70%',
          },
          track: {
            show: true,
            background: '#FFBB1D',
            strokeWidth: '100%',
            opacity: 0.2,
          },
          dataLabels: {
            show: true,
            name: {
              show: false,
            },
            value: {
              show: true,
              fontSize: '30px',
              fontWeight: 900,
              color: '#002434',
              offsetY: 12,
            }
          }
        },
      },
      colors: ['#FFBB1D'],
      legend: {
        show: false
      },
    });
    crChart.render();
  </script>
</div>