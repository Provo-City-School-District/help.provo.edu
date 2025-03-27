//================================= Charts =================================
// Chart for all techs open tickets.
window.addEventListener("load", function () {
  // tech department
  var allTechsChart = new CanvasJS.Chart("techOpenTicket", {
    height: 1000,
    animationEnabled: true,
    title: {
      text: "All Technicians Open Tickets",
    },
    axisY: {
      title: "Ticket Count",
      includeZero: true,
      labelFontSize: 14,
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
      labelFontSize: 14,
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "inside",
        // indexLabelFontWeight: "bold",
        // indexLabelFontColor: "white",
        indexLabelFontSize: 1,
        dataPoints: allTechs,
        click: function (e) {
          window.location.href = e.dataPoint.url;
        },
      },
    ],
  });
  allTechsChart.render();

  // by location
  var byLocationChart = new CanvasJS.Chart("byLocation", {
    height: 1000,
    animationEnabled: true,
    title: {
      text: "Department Open Tickets By Location",
    },
    axisY: {
      title: "Ticket Count",
      includeZero: true,
      labelFontSize: 14,
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
      labelFontSize: 14,
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "inside",
        // indexLabelFontWeight: "bold",
        // indexLabelFontColor: "white",
        indexLabelFontSize: 1,
        dataPoints: byLocation,
      },
    ],
  });
  byLocationChart.render();

  //field techs open tickets
  // var fieldTechOpenChart = new CanvasJS.Chart("fieldTechOpen", {
  //   height: 1000,
  //   animationEnabled: true,
  //   title: {
  //     text: "Field Tech's Open Tickets",
  //   },
  //   axisY: {
  //     title: "Ticket Count",
  //     includeZero: true,
  //   },
  //   axisX: {
  //     interval: 1, // Set the interval of the x-axis labels to 1
  //   },
  //   data: [
  //     {
  //       type: "bar",
  //       yValueFormatString: "#,##",
  //       indexLabel: "{y}",
  //       indexLabelPlacement: "outside",
  //       indexLabelFontWeight: "bolder",
  //       indexLabelFontColor: "white",
  //       indexLabelFontSize: 2,
  //       dataPoints: fieldTechOpen,
  //     },
  //   ],
  // });
  // fieldTechOpenChart.render();
});
