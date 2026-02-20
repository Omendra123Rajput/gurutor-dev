 <script>
    var crValues = {};

    
    //crValues.valueFTA = ((window.lectoraModuleVars.VarStatsCRFTACorrect / window.lectoraModuleVars.VarStatsCRFTATotal) * 100) || 0;
     crValues.valueFTA = 78;
    document.getElementById('valueFTA').textContent = Math.round(crValues.valueFTA) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    //crValues.valueSTRG = ((window.lectoraModuleVars.VarStatsCRSTRGCorrect / window.lectoraModuleVars.VarStatsCRSTRGTotal) * 100) || 0;
     crValues.valueSTRG = 80;
    document.getElementById('valueSTRG').textContent = Math.round(crValues.valueSTRG) + '%';
    if (crValues.valueSTRG && crValues.valueSTRG >= 90) {
      document.getElementById('strengthen_the_argument_green').style.display = 'inline';
    } else if (crValues.valueSTRG && crValues.valueSTRG >= 70) {
      document.getElementById('strengthen_the_argument_yellow').style.display = 'inline';
    } else if (crValues.valueSTRG && crValues.valueSTRG < 70) {
      document.getElementById('strengthen_the_argument_red').style.display = 'inline';      
    }

    // crValues.valueWKN = ((window.lectoraModuleVars.VarStatsCRWKNCorrect / window.lectoraModuleVars.VarStatsCRWKNTotal) * 100) || 0;
    crValues.valueWKN = 90;
    document.getElementById('valueWKN').textContent = Math.round(crValues.valueWKN) + '%';
    if (crValues.valueWKN && crValues.valueWKN >= 90) {
      document.getElementById('weaken_the_argument_green').style.display = 'inline';
    } else if (crValues.valueWKN && crValues.valueWKN >= 70) {
      document.getElementById('weaken_the_argument_yellow').style.display = 'inline';
    } else if (crValues.valueWKN && crValues.valueWKN < 70) {
      document.getElementById('weaken_the_argument_red').style.display = 'inline';      
    }

    // crValues.valueEVAL = ((window.lectoraModuleVars.VarStatsCREVALCorrect / window.lectoraModuleVars.VarStatsCREVALTotal) * 100) || 0;
    crValues.valueEVAL = 48;
    document.getElementById('valueEVAL').textContent = Math.round(crValues.valueEVAL) + '%';
    if (crValues.valueEVAL && crValues.valueEVAL >= 90) {
      document.getElementById('evaluate_the_argument_green').style.display = 'inline';
    } else if (crValues.valueEVAL && crValues.valueEVAL >= 70) {
      document.getElementById('evaluate_the_argument_yellow').style.display = 'inline';
    } else if (crValues.valueEVAL && crValues.valueEVAL < 70) {
      document.getElementById('evaluate_the_argument_red').style.display = 'inline';      
    }

    // crValues.valueROLE = ((window.lectoraModuleVars.VarStatsCRROLECorrect / window.lectoraModuleVars.VarStatsCRROLETotal) * 100) || 0;
    crValues.valueROLE = 40;
    document.getElementById('valueROLE').textContent = Math.round(crValues.valueROLE) + '%';
    if (crValues.valueROLE && crValues.valueROLE >= 90) {
      document.getElementById('describe_the_role_green').style.display = 'inline';
    } else if (crValues.valueROLE && crValues.valueROLE >= 70) {
      document.getElementById('describe_the_role_yellow').style.display = 'inline';
    } else if (crValues.valueROLE && crValues.valueROLE < 70) {
      document.getElementById('describe_the_role_red').style.display = 'inline';      
    }

    // crValues.valueINF = ((window.lectoraModuleVars.VarStatsCRINFCorrect / window.lectoraModuleVars.VarStatsCRINFTotal) * 100) || 0;
    crValues.valueINF = 50;
    document.getElementById('valueINF').textContent = Math.round(crValues.valueINF) + '%';
    if (crValues.valueINF && crValues.valueINF >= 90) {
      document.getElementById('inference_green').style.display = 'inline';
    } else if (crValues.valueINF && crValues.valueINF >= 70) {
      document.getElementById('inference_yellow').style.display = 'inline';
    } else if (crValues.valueINF && crValues.valueINF < 70) {
      document.getElementById('inference_red').style.display = 'inline';      
    }

    // crValues.valueDIS = ((window.lectoraModuleVars.VarStatsCRDISCorrect / window.lectoraModuleVars.VarStatsCRDISTotal) * 100) || 0;
    crValues.valueDIS = 90;
    document.getElementById('valueDIS').textContent = Math.round(crValues.valueDIS) + '%';
    if (crValues.valueDIS && crValues.valueDIS >= 90) {
      document.getElementById('explain_the_discrepancy_green').style.display = 'inline';
    } else if (crValues.valueDIS && crValues.valueDIS >= 70) {
      document.getElementById('explain_the_discrepancy_yellow').style.display = 'inline';
    } else if (crValues.valueDIS && crValues.valueDIS < 70) {
      document.getElementById('explain_the_discrepancy_red').style.display = 'inline';      
    }

    //crValues.valuePLA = ((window.lectoraModuleVars.VarStatsCRPLACorrect / window.lectoraModuleVars.VarStatsCRPLATotal) * 100) || 0;
    crValues.valuePLA = 80;
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

    // crValues.valueStep1 = ((window.lectoraModuleVars.VarStatsCRStep1Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    crValues.valueStep1 = 92;
    document.getElementById('valueStep1').textContent = Math.round(crValues.valueStep1) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    // crValues.valueStep2 = ((window.lectoraModuleVars.VarStatsCRStep2Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    crValues.valueStep2 = 37;
    document.getElementById('valueStep2').textContent = Math.round(crValues.valueStep2) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    // crValues.valueStep3 = ((window.lectoraModuleVars.VarStatsCRStep3Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    crValues.valueStep3 = 80;
    document.getElementById('valueStep3').textContent = Math.round(crValues.valueStep3) + '%';
    if (crValues.valueFTA && crValues.valueFTA >= 90) {
      document.getElementById('find_the_assumption_green').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA >= 70) {
      document.getElementById('find_the_assumption_yellow').style.display = 'inline';
    } else if (crValues.valueFTA && crValues.valueFTA < 70) {
      document.getElementById('find_the_assumption_red').style.display = 'inline';      
    }

    // crValues.valueStep4 = ((window.lectoraModuleVars.VarStatsCRStep4Correct / window.lectoraModuleVars.VarStatsCRQuestionsTotal) * 100) || 0;
    crValues.valueStep4 = 90;
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