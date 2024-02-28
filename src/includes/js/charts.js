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
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "outside",
        indexLabelFontWeight: "bolder",
        indexLabelFontColor: "white",
        indexLabelFontSize: 4,
        dataPoints: allTechs,
      },
    ],
  });
  allTechsChart.render();

  // by location
  var byLocationChart = new CanvasJS.Chart("byLocation", {
    height: 1000,
    animationEnabled: true,
    title: {
      text: "All Open Tickets By Location",
    },
    axisY: {
      title: "Ticket Count",
      includeZero: true,
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "outside",
        indexLabelFontWeight: "bolder",
        indexLabelFontColor: "white",
        indexLabelFontSize: 4,
        dataPoints: byLocation,
      },
    ],
  });
  byLocationChart.render();

  //field techs open tickets
  var fieldTechOpenChart = new CanvasJS.Chart("fieldTechOpen", {
    height: 1000,
    animationEnabled: true,
    title: {
      text: "Field Tech's Open Tickets",
    },
    axisY: {
      title: "Ticket Count",
      includeZero: true,
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "outside",
        indexLabelFontWeight: "bolder",
        indexLabelFontColor: "white",
        indexLabelFontSize: 4,
        dataPoints: fieldTechOpen,
      },
    ],
  });
  fieldTechOpenChart.render();
});
