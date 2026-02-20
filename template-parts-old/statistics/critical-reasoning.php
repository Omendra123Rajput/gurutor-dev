<div id="cr-box" class="box">
  <div class="nav">
    <button type="button" class="button selected" onclick="document.querySelector('#cr-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#cr-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('cr_by_question_type').classList.remove('hidden')"><span class="text">BY QUESTION TYPE</span></button>
    <button type="button" class="button" onclick="document.querySelector('#cr-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#cr-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('cr_by_argument_type').classList.remove('hidden')"><span class="text">BY ARGUMENT TYPE</span></button>
    <button type="button" class="button" onclick="document.querySelector('#cr-box .button.selected').classList.remove('selected');this.classList.add('selected');document.querySelector('#cr-box .statistics-wrapper:not(.hidden)').classList.add('hidden');document.getElementById('cr_by_process').classList.remove('hidden')"><span class="text">BY PROCESS</span></button>
  </div>
  <div id="cr_by_question_type" class="statistics-wrapper">
    <div class="lists">
      <div class="list">
        <p class="header">The Assumption Family</p>
        <ul>
          <li id="find_the_assumption">
            Find the Assumption <span class="percentage" id="valueFTA">0%</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="find_the_assumption_green">
              <g id="checkmark" transform="translate(-806.425 -2424.426)">
                <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
                  <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
                </g>
                <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
              </g>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="find_the_assumption_yellow">
              <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="find_the_assumption_red">
              <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
                <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
                <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
                <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
              </g>
            </svg>
          </li>
          <li id="strengthen_the_argument">
            Strengthen the Argument <span class="percentage" id="valueSTRG">0%</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="strengthen_the_argument_green">
              <g id="checkmark" transform="translate(-806.425 -2424.426)">
                <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
                  <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
                </g>
                <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
              </g>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="strengthen_the_argument_yellow">
              <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="strengthen_the_argument_red">
              <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
                <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
                <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
                <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
              </g>
            </svg>
          </li>
          <li id="weaken_the_argument">
            Weaken the Argument <span class="percentage" id="valueWKN">0%</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="weaken_the_argument_green">
              <g id="checkmark" transform="translate(-806.425 -2424.426)">
                <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
                  <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
                </g>
                <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
              </g>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="weaken_the_argument_yellow">
              <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="weaken_the_argument_red">
              <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
                <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
                <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
                <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
              </g>
            </svg>
          </li>
          <li id="evaluate_the_argument">
            Evaluate the Argument <span class="percentage" id="valueEVAL">0%</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="evaluate_the_argument_green">
              <g id="checkmark" transform="translate(-806.425 -2424.426)">
                <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
                  <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
                </g>
                <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
              </g>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="evaluate_the_argument_yellow">
              <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="evaluate_the_argument_red">
              <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
                <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
                <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
                <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
              </g>
            </svg>
          </li>
        </ul>
      </div>
      <div class="list">
        <p class="header">The Structure Family</p>
        <ul>
          <li id="describe_the_role">
            Describe the Role <span class="percentage" id="valueROLE">0%</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="describe_the_role_green">
              <g id="checkmark" transform="translate(-806.425 -2424.426)">
                <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
                  <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
                </g>
                <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
              </g>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="describe_the_role_yellow">
              <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="describe_the_role_red">
              <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
                <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
                <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
                <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
              </g>
            </svg>
          </li>
        </ul>
      </div>
      <div class="list">
        <p class="header">The Evidence Family</p>
        <ul>
          <li id="inference">
            Inference <span class="percentage" id="valueINF">0%</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="inference_green">
              <g id="checkmark" transform="translate(-806.425 -2424.426)">
                <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
                  <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
                </g>
                <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
              </g>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="inference_yellow">
              <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="inference_red">
              <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
                <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
                <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
                <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
              </g>
            </svg>
          </li>
          <li id="explain_the_discrepancy">
            Explain the Discrepancy <span class="percentage" id="valueDIS">0%</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="25.212" height="25.214" viewBox="0 0 25.212 25.214" style="display:none" id="explain_the_discrepancy_green">
              <g id="checkmark" transform="translate(-806.425 -2424.426)">
                <g id="Group_2571" data-name="Group 2571" transform="translate(557.604 1617.843)">
                  <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#15d500" stroke="rgba(0,0,0,0)" stroke-width="1"/>
                </g>
                <path id="Path_4351" data-name="Path 4351" d="M4.87,7.986,0,3.116,1.573,1.543l3.3,3.222,6.484-6.425L12.927-.087Z" transform="translate(812.568 2434.662)" fill="#fff"/>
              </g>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="21.296" height="21.296" viewBox="0 0 21.296 21.296" style="display:none" id="explain_the_discrepancy_yellow">
              <path id="arrow-icon" d="M1969.647-534.495A10.648,10.648,0,0,0,1959-523.847a10.648,10.648,0,0,0,10.648,10.648,10.648,10.648,0,0,0,10.648-10.648A10.648,10.648,0,0,0,1969.647-534.495Zm5.08,12.731a1.2,1.2,0,0,1-1.2,1.2,1.2,1.2,0,0,1-1.2-1.2v-3.09l-5.729,5.729a1.192,1.192,0,0,1-.845.35,1.191,1.191,0,0,1-.845-.35,1.2,1.2,0,0,1,0-1.691l5.7-5.7-2.987-.008a1.2,1.2,0,0,1-1.192-1.2,1.2,1.2,0,0,1,1.2-1.192h0l5.708.016a1.187,1.187,0,0,1,.873.221,1.221,1.221,0,0,1,.123.1l.016.013.005.006a1.191,1.191,0,0,1,.362.856Z" transform="translate(-1958.999 534.495)" fill="#ffbb1d"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24.065" height="24.067" viewBox="0 0 24.065 24.067" style="display:none" id="explain_the_discrepancy_red">
              <g id="exclamation-mark" transform="translate(-249.396 -807.157)">
                <path id="Path_4349" data-name="Path 4349" d="M10.489,0A10.488,10.488,0,1,1,0,10.488,10.488,10.488,0,0,1,10.489,0Z" transform="translate(249.396 827.864) rotate(-80.783)" fill="#eb2e29"/>
                <path id="Path_4347" data-name="Path 4347" d="M290.584,835.509a1.1,1.1,0,0,1-1.1-1.1v-9.6a1.1,1.1,0,1,1,2.193,0v9.6A1.1,1.1,0,0,1,290.584,835.509Z" transform="translate(-29.156 -12.062)" fill="#fff"/>
                <path id="Path_4348" data-name="Path 4348" d="M290.587,872.974a1.1,1.1,0,1,1,.776-.321A1.1,1.1,0,0,1,290.587,872.974Z" transform="translate(-29.158 -46.267)" fill="#fff"/>
              </g>
            </svg>
          </li>
        </ul>
      </div>
    </div>
    <div id="bqt-chart"></div>
  </div>
  <div id="cr_by_argument_type" class="hidden statistics-wrapper">
    <div class="statistic">
      <p class="percentage"><span id="valuePLA">0</span>%</p>
      <input type="range" id="valueRangePLA" name="points" min="0" max="100" value="0" disabled>
      <p class="label">PLAN ARGUMENTS</p>
    </div>
    <div class="statistic">
      <p class="percentage"><span id="valueEXP">0</span>%</p>
      <input type="range" id="valueRangeEXP" name="points" min="0" max="100" value="0" disabled>
      <p class="label">EXPLANATION ARGUMENTS</p>
    </div>
    <div class="statistic">
      <p class="percentage"><span id="valueREG">0</span>%</p>
      <input type="range" id="valueRangeREG" name="points" min="0" max="100" value="0" disabled>
      <p class="label">REGULAR ARGUMENTS</p>
    </div>
  </div>
  <div id="cr_by_process" class="hidden statistics-wrapper">
    <div class="statistic">
      <svg xmlns="http://www.w3.org/2000/svg" width="203.985" height="110.284" viewBox="0 0 203.985 110.284">
        <path id="chevron-1" d="M-3.219,415.541v72.6c0,10.4,4.887,18.84,10.914,18.84H168.232c4.521,0,10.576-4.795,13.523-10.713l16.8-33.716c2.948-5.916,2.948-15.51,0-21.426l-16.8-33.716c-2.947-5.918-9-10.713-13.523-10.713H7.7C1.668,396.7-3.219,405.137-3.219,415.541Z" transform="translate(3.219 -396.701)" fill="#00276a"/>
      </svg>
      <div class="content">
        <span style="display: block;text-align: center;font: normal normal bold 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Step 1</span>
        <span style="display: block;text-align: center;font: normal normal normal 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">ID Question</span>
        <span style="display: block;text-align: center;font: normal normal 600 16px/24px Nunito Sans;letter-spacing: 1.2px;color: #FFFFFF;text-transform: uppercase;opacity: 1;" id="valueStep1">0%</span>
      </div>
    </div>
    <div class="statistic">
      <svg xmlns="http://www.w3.org/2000/svg" width="208.785" height="110.284" viewBox="0 0 208.785 110.284">
        <path id="chevron-2" d="M154.226,410.986l13.24,26.574c3.93,7.887,3.93,20.679,0,28.568L154.226,492.7c-3.93,7.887-2.231,14.283,3.8,14.283H328.358c4.521,0,10.576-4.795,13.524-10.713l16.8-33.716c2.948-5.918,2.948-15.508,0-21.426l-16.8-33.716c-2.948-5.918-9-10.713-13.524-10.713H158.024C152,396.7,150.3,403.1,154.226,410.986Z" transform="translate(-152.109 -396.701)" fill="#000ab4"/>
      </svg>
      <div class="content">
        <span style="display: block;text-align: center;font: normal normal bold 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Step 2</span>
        <span style="display: block;text-align: center;font: normal normal normal 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Extract Key Info</span>
        <span style="display: block;text-align: center;font: normal normal 600 16px/24px Nunito Sans;letter-spacing: 1.2px;color: #FFFFFF;text-transform: uppercase;opacity: 1;" id="valueStep2">0%</span>
      </div>
    </div>
    <div class="statistic">
      <svg xmlns="http://www.w3.org/2000/svg" width="208.785" height="110.284" viewBox="0 0 208.785 110.284">
        <path id="chevron-2" d="M154.226,410.986l13.24,26.574c3.93,7.887,3.93,20.679,0,28.568L154.226,492.7c-3.93,7.887-2.231,14.283,3.8,14.283H328.358c4.521,0,10.576-4.795,13.524-10.713l16.8-33.716c2.948-5.918,2.948-15.508,0-21.426l-16.8-33.716c-2.948-5.918-9-10.713-13.524-10.713H158.024C152,396.7,150.3,403.1,154.226,410.986Z" transform="translate(-152.109 -396.701)" fill="#000ab4"/>
      </svg>
      <div class="content">
        <span style="display: block;text-align: center;font: normal normal bold 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Step 2</span>
        <span style="display: block;text-align: center;font: normal normal normal 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Argument Type</span>
        <span style="display: block;text-align: center;font: normal normal 600 16px/24px Nunito Sans;letter-spacing: 1.2px;color: #FFFFFF;text-transform: uppercase;opacity: 1;" id="valueStep2">0%</span>
      </div>
    </div>
    <div class="statistic">
      <svg xmlns="http://www.w3.org/2000/svg" width="208.785" height="110.284" viewBox="0 0 208.785 110.284">
        <path id="chevron-2" d="M154.226,410.986l13.24,26.574c3.93,7.887,3.93,20.679,0,28.568L154.226,492.7c-3.93,7.887-2.231,14.283,3.8,14.283H328.358c4.521,0,10.576-4.795,13.524-10.713l16.8-33.716c2.948-5.918,2.948-15.508,0-21.426l-16.8-33.716c-2.948-5.918-9-10.713-13.524-10.713H158.024C152,396.7,150.3,403.1,154.226,410.986Z" transform="translate(-152.109 -396.701)" fill="#4790ff"/>
      </svg>
      <div class="content">
        <span style="display: block;text-align: center;font: normal normal bold 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Step 3</span>
        <span style="display: block;text-align: center;font: normal normal normal 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Determine Target</span>
        <span style="display: block;text-align: center;font: normal normal 600 16px/24px Nunito Sans;letter-spacing: 1.2px;color: #FFFFFF;text-transform: uppercase;opacity: 1;" id="valueStep3">0%</span>
      </div>
    </div>
    <div class="statistic">
      <svg xmlns="http://www.w3.org/2000/svg" width="208.785" height="110.284" viewBox="0 0 208.785 110.284">
        <path id="chevron-2" d="M154.226,410.986l13.24,26.574c3.93,7.887,3.93,20.679,0,28.568L154.226,492.7c-3.93,7.887-2.231,14.283,3.8,14.283H328.358c4.521,0,10.576-4.795,13.524-10.713l16.8-33.716c2.948-5.918,2.948-15.508,0-21.426l-16.8-33.716c-2.948-5.918-9-10.713-13.524-10.713H158.024C152,396.7,150.3,403.1,154.226,410.986Z" transform="translate(-152.109 -396.701)" fill="#97c0ff"/>
      </svg>
      <div class="content">
        <span style="display: block;text-align: center;font: normal normal bold 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Step 4</span>
        <span style="display: block;text-align: center;font: normal normal normal 18px/28px Open Sans;letter-spacing: 0px;color: #FFFFFF;">Eliminate</span>
        <span style="display: block;text-align: center;font: normal normal 600 16px/24px Nunito Sans;letter-spacing: 1.2px;color: #FFFFFF;text-transform: uppercase;opacity: 1;" id="valueStep4">0%</span>
      </div>
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
    #cr-box #cr_by_question_type {
      display: flex;
      align-items: center;
      width: 100%;
      padding: 0 26px;
    }
    #cr-box #cr_by_question_type .lists {
      display: flex;
      flex-direction: column;
      color: #002434;
      opacity: 1;
      width: 100%;
      gap: 40px;
    }
    #cr-box #cr_by_question_type .lists .list .header {
      text-align: left;
      font: normal normal bold 18px/28px Open Sans;
      letter-spacing: 0px;
      color: #002434;
    }
    #cr-box #cr_by_question_type .lists .list ul {
      display: flex;
      flex-direction: column;
      gap: 5px;
      padding-left: 34px;
      margin-top: 5px;
    }
    #cr-box #cr_by_question_type .lists .list li {
      display: flex;
      position: relative;
      text-align: left;
      font: normal normal normal 18px/28px Open Sans;
      letter-spacing: 0px;
      color: #002434;
    }
    #cr-box #cr_by_question_type .lists .list li .percentage {
      text-align: right;
      margin-left: auto;
      margin-right: 10px;
      font: normal normal bold 18px/28px Open Sans;
      letter-spacing: 0px;
      color: #002434;
    }
    #cr-box #cr_by_question_type .lists .list li:before {
      content: "";
      position: absolute;
      height: 20px;
      width: 20px;
      border-radius: 50%;
      top: 50%;
      left: -34px;
      transform: translateY(-50%);
      background-color: #002434;
    }
    #cr-box #cr_by_question_type .lists .list li#find_the_assumption:before { background-color: #000563; }
    #cr-box #cr_by_question_type .lists .list li#strengthen_the_argument:before { background-color: #0009A3; }
    #cr-box #cr_by_question_type .lists .list li#weaken_the_argument:before { background-color: #004FC7; }
    #cr-box #cr_by_question_type .lists .list li#evaluate_the_argument:before { background-color: #005AE2; }
    #cr-box #cr_by_question_type .lists .list li#describe_the_role:before { background-color: #D98DFF; }
    #cr-box #cr_by_question_type .lists .list li#inference:before { background-color: #A3A3A3; }
    #cr-box #cr_by_question_type .lists .list li#explain_the_discrepancy:before { background-color: #C9C8C8; }
    #cr-box #cr_by_question_type #bqt-chart {
      width: 100%;
    }

    #cr-box #cr_by_argument_type {
      display: flex;
      gap: 21px;
    }
    #cr-box #cr_by_argument_type .statistic {
      width: 100%;
      background-color: #F5F5F5;
      text-align: center;
      padding: 38px 33px;
      border-radius: 23px;
    }
    #cr-box #cr_by_argument_type .statistic .percentage {
      text-align: center;
      font: normal normal bold 42px/57px Open Sans;
      letter-spacing: -1.05px;
      color: #002434;
      opacity: 1;
      margin-bottom: 5px;
    }
    #cr-box #cr_by_argument_type .statistic .label {
      font: normal normal 600 16px/24px Nunito Sans;
      letter-spacing: 1.2px;
      color: #002434;
      text-transform: uppercase;
      opacity: 1;
      margin-top: 15px;
    }

    #cr-box #cr_by_process {
      display: flex;
    }
    #cr-box #cr_by_process .statistic {
      width: 100%;
      position: relative;
    }
    #cr-box #cr_by_process .statistic .content {
      position: absolute;
      top: 50%;
      left: 0;
      right: 8%;
      transform: translateY(-50%);
    }
    #cr-box #cr_by_process .statistic:not(:first-child) svg {
      margin-left: -17px;
    }
    #cr-box #cr_by_process .statistic:nth-child(2) svg {
      margin-left: -14px;
    }

    #cr-box input[type="range"] {
      background-color: #E3E2E2;
      border-radius: 500px;
      height: 8px;
    }
    #cr-box input[type="range"]::-moz-range-progress {
      background: #005AE2;
      border-radius: 500px;
      height: 8px;
    }
    #cr-box input[type="range"]::-moz-range-thumb {
      appearance: none;
      -moz-appearance: none;
      -webkit-appearance: none;
      height: 0;
      width: 0;
    }
  </style>

  <script>
    var crValues = {};
    crValues.valueFTA = ((window.lectoraModuleVars.VarStatsCRFTACorrect / window.lectoraModuleVars.VarStatsCRFTATotal) * 100) || 0;
    document.getElementById('valueFTA').textContent = Math.round(crValues.valueFTA) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    crValues.valueSTRG = ((window.lectoraModuleVars.VarStatsCRSTRGCorrect / window.lectoraModuleVars.VarStatsCRSTRGTotal) * 100) || 0;
    document.getElementById('valueSTRG').textContent = Math.round(crValues.valueSTRG) + '%';
    if (crValues.valueSTRG && crValues.valueSTRG >= 90) {
      document.getElementById('strengthen_the_argument_green').style.display = 'inline';
    } else if (crValues.valueSTRG && crValues.valueSTRG >= 70) {
      document.getElementById('strengthen_the_argument_yellow').style.display = 'inline';
    } else if (crValues.valueSTRG && crValues.valueSTRG < 70) {
      document.getElementById('strengthen_the_argument_red').style.display = 'inline';      
    }

    crValues.valueWKN = ((window.lectoraModuleVars.VarStatsCRWKNCorrect / window.lectoraModuleVars.VarStatsCRWKNTotal) * 100) || 0;
    document.getElementById('valueWKN').textContent = Math.round(crValues.valueWKN) + '%';
    if (crValues.valueWKN && crValues.valueWKN >= 90) {
      document.getElementById('weaken_the_argument_green').style.display = 'inline';
    } else if (crValues.valueWKN && crValues.valueWKN >= 70) {
      document.getElementById('weaken_the_argument_yellow').style.display = 'inline';
    } else if (crValues.valueWKN && crValues.valueWKN < 70) {
      document.getElementById('weaken_the_argument_red').style.display = 'inline';      
    }

    crValues.valueEVAL = ((window.lectoraModuleVars.VarStatsCREVALCorrect / window.lectoraModuleVars.VarStatsCREVALTotal) * 100) || 0;
    document.getElementById('valueEVAL').textContent = Math.round(crValues.valueEVAL) + '%';
    if (crValues.valueEVAL && crValues.valueEVAL >= 90) {
      document.getElementById('evaluate_the_argument_green').style.display = 'inline';
    } else if (crValues.valueEVAL && crValues.valueEVAL >= 70) {
      document.getElementById('evaluate_the_argument_yellow').style.display = 'inline';
    } else if (crValues.valueEVAL && crValues.valueEVAL < 70) {
      document.getElementById('evaluate_the_argument_red').style.display = 'inline';      
    }

    crValues.valueROLE = ((window.lectoraModuleVars.VarStatsCRROLECorrect / window.lectoraModuleVars.VarStatsCRROLETotal) * 100) || 0;
    document.getElementById('valueROLE').textContent = Math.round(crValues.valueROLE) + '%';
    if (crValues.valueROLE && crValues.valueROLE >= 90) {
      document.getElementById('describe_the_role_green').style.display = 'inline';
    } else if (crValues.valueROLE && crValues.valueROLE >= 70) {
      document.getElementById('describe_the_role_yellow').style.display = 'inline';
    } else if (crValues.valueROLE && crValues.valueROLE < 70) {
      document.getElementById('describe_the_role_red').style.display = 'inline';      
    }

    crValues.valueINF = ((window.lectoraModuleVars.VarStatsCRINFCorrect / window.lectoraModuleVars.VarStatsCRINFTotal) * 100) || 0;
    document.getElementById('valueINF').textContent = Math.round(crValues.valueINF) + '%';
    if (crValues.valueINF && crValues.valueINF >= 90) {
      document.getElementById('inference_green').style.display = 'inline';
    } else if (crValues.valueINF && crValues.valueINF >= 70) {
      document.getElementById('inference_yellow').style.display = 'inline';
    } else if (crValues.valueINF && crValues.valueINF < 70) {
      document.getElementById('inference_red').style.display = 'inline';      
    }

    crValues.valueDIS = ((window.lectoraModuleVars.VarStatsCRDISCorrect / window.lectoraModuleVars.VarStatsCRDISTotal) * 100) || 0;
    document.getElementById('valueDIS').textContent = Math.round(crValues.valueDIS) + '%';
    if (crValues.valueDIS && crValues.valueDIS >= 90) {
      document.getElementById('explain_the_discrepancy_green').style.display = 'inline';
    } else if (crValues.valueDIS && crValues.valueDIS >= 70) {
      document.getElementById('explain_the_discrepancy_yellow').style.display = 'inline';
    } else if (crValues.valueDIS && crValues.valueDIS < 70) {
      document.getElementById('explain_the_discrepancy_red').style.display = 'inline';      
    }

    crValues.valuePLA = ((window.lectoraModuleVars.VarStatsCRPLACorrect / window.lectoraModuleVars.VarStatsCRPLATotal) * 100) || 0;
    document.getElementById('valuePLA').textContent = Math.round(crValues.valuePLA);
    document.getElementById('valueRangePLA').value = Math.round(crValues.valuePLA);

    crValues.valueEXP = ((window.lectoraModuleVars.VarStatsCREXPCorrect / window.lectoraModuleVars.VarStatsCREXPTotal) * 100) || 0;
    document.getElementById('valueEXP').textContent = Math.round(crValues.valueEXP);
    document.getElementById('valueRangeEXP').value = Math.round(crValues.valueEXP);

    crValues.valueREG = ((window.lectoraModuleVars.VarStatsCRREGCorrect / window.lectoraModuleVars.VarStatsCRREGTotal) * 100) || 0;
    document.getElementById('valueREG').textContent = Math.round(crValues.valueREG);
    document.getElementById('valueRangeREG').value = Math.round(crValues.valueREG);
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    crValues.valueStep1 = ((window.lectoraModuleVars.VarStatsCRStep1Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    document.getElementById('valueStep1').textContent = Math.round(crValues.valueStep1) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    crValues.valueStep2 = ((window.lectoraModuleVars.VarStatsCRStep2Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    document.getElementById('valueStep2').textContent = Math.round(crValues.valueStep2) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    crValues.valueStep3 = ((window.lectoraModuleVars.VarStatsCRStep3Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    document.getElementById('valueStep3').textContent = Math.round(crValues.valueStep3) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    crValues.valueStep4 = ((window.lectoraModuleVars.VarStatsCRStep4Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    document.getElementById('valueStep4').textContent = Math.round(crValues.valueStep4) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    var bqtChart = new ApexCharts(document.querySelector("#bqt-chart"), {
      series: [Math.round(crValues.valueFTA), Math.round(crValues.valueSTRG), Math.round(crValues.valueWKN), Math.round(crValues.valueEVAL), Math.round(crValues.valueROLE), Math.round(crValues.valueINF), Math.round(crValues.valueDIS)],
      chart: {
        height: 450,
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '40%',
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
        }
      },
      colors: ['#000563', '#0009A3', '#004FC7', '#005AE2', '#D98DFF', '#A3A3A3', '#C9C8C8'],
      labels: [],
    });
    bqtChart.render();
  </script>
</div>