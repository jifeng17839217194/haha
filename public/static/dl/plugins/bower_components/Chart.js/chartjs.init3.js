$( document ).ready(function() {
    
    var ctx1 = document.getElementById("chart1").getContext("2d");
    var data1 = {
        labels: ["January", "February", "March", "April", "May", "June", "July"],
        datasets: [
            {
                label: "My First dataset",
                fillColor: "rgba(255,118,118,0.8)",
                strokeColor: "rgba(255,118,118,0.8)",
                highlightFill: "rgba(255,118,118,1)",
                highlightStroke: "rgba(255,118,118,1)",
                data: [10, 30, 80, 61, 26, 75, 40]
            },
            // {
                // label: "My Second dataset",
                // fillColor: "rgba(180,193,215,0.8)",
                // strokeColor: "rgba(180,193,215,0.8)",
                // highlightFill: "rgba(180,193,215,1)",
                // highlightStroke: "rgba(180,193,215,1)",
                // data: [28, 48, 40, 19, 86, 27, 90]
            // }
        ]
    };
    
    var chart1 = new Chart(ctx1).Bar(data1, {
        scaleBeginAtZero : true,
        scaleShowGridLines : true,
        scaleGridLineColor : "rgba(0,0,0,.005)",
        scaleGridLineWidth : 0,
        scaleShowHorizontalLines: true,
        scaleShowVerticalLines: true,
        barShowStroke : true,
        barStrokeWidth : 0,
		tooltipCornerRadius: 2,
        barDatasetSpacing : 3,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].fillColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    var ctx2 = document.getElementById("chart2").getContext("2d");
    var data2 = {
        labels: ["January", "February", "March", "April", "May", "June", "July"],
        datasets: [
            {
                label: "My First dataset",
                fillColor: "rgba(255,118,118,0.8)",
                strokeColor: "rgba(255,118,118,0.8)",
                highlightFill: "rgba(255,118,118,1)",
                highlightStroke: "rgba(255,118,118,1)",
                data: [10, 30, 80, 61, 26, 75, 40]
            },
            // {
                // label: "My Second dataset",
                // fillColor: "rgba(180,193,215,0.8)",
                // strokeColor: "rgba(180,193,215,0.8)",
                // highlightFill: "rgba(180,193,215,1)",
                // highlightStroke: "rgba(180,193,215,1)",
                // data: [28, 48, 40, 19, 86, 27, 90]
            // }
        ]
    };
    
    var chart2 = new Chart(ctx2).Bar(data2, {
        scaleBeginAtZero : true,
        scaleShowGridLines : true,
        scaleGridLineColor : "rgba(0,0,0,.005)",
        scaleGridLineWidth : 0,
        scaleShowHorizontalLines: true,
        scaleShowVerticalLines: true,
        barShowStroke : true,
        barStrokeWidth : 0,
		tooltipCornerRadius: 2,
        barDatasetSpacing : 3,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].fillColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
	    var ctx4 = document.getElementById("chart4").getContext("2d");
    var data4 = {
        labels: ["January", "February", "March", "April", "May", "June", "July"],
        datasets: [
            {
                label: "My First dataset",
                fillColor: "rgba(255,118,118,0.8)",
                strokeColor: "rgba(255,118,118,0.8)",
                highlightFill: "rgba(255,118,118,1)",
                highlightStroke: "rgba(255,118,118,1)",
                data: [10, 30, 80, 61, 26, 75, 40]
            },
            // {
                // label: "My Second dataset",
                // fillColor: "rgba(180,193,215,0.8)",
                // strokeColor: "rgba(180,193,215,0.8)",
                // highlightFill: "rgba(180,193,215,1)",
                // highlightStroke: "rgba(180,193,215,1)",
                // data: [28, 48, 40, 19, 86, 27, 90]
            // }
        ]
    };
    
    var chart4 = new Chart(ctx4).Bar(data4, {
        scaleBeginAtZero : true,
        scaleShowGridLines : true,
        scaleGridLineColor : "rgba(0,0,0,.005)",
        scaleGridLineWidth : 0,
        scaleShowHorizontalLines: true,
        scaleShowVerticalLines: true,
        barShowStroke : true,
        barStrokeWidth : 0,
		tooltipCornerRadius: 2,
        barDatasetSpacing : 3,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].fillColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    var ctx5 = document.getElementById("chart5").getContext("2d");
    var data5 = {
        labels: ["January", "February", "March", "April", "May", "June", "July"],
        datasets: [
            {
                label: "My First dataset",
                fillColor: "rgba(255,118,118,0.8)",
                strokeColor: "rgba(255,118,118,0.8)",
                highlightFill: "rgba(255,118,118,1)",
                highlightStroke: "rgba(255,118,118,1)",
                data: [10, 30, 80, 61, 26, 75, 40]
            },
            // {
                // label: "My Second dataset",
                // fillColor: "rgba(180,193,215,0.8)",
                // strokeColor: "rgba(180,193,215,0.8)",
                // highlightFill: "rgba(180,193,215,1)",
                // highlightStroke: "rgba(180,193,215,1)",
                // data: [28, 48, 40, 19, 86, 27, 90]
            // }
        ]
    };
    
    var chart5 = new Chart(ctx5).Bar(data5, {
        scaleBeginAtZero : true,
        scaleShowGridLines : true,
        scaleGridLineColor : "rgba(0,0,0,.005)",
        scaleGridLineWidth : 0,
        scaleShowHorizontalLines: true,
        scaleShowVerticalLines: true,
        barShowStroke : true,
        barStrokeWidth : 0,
		tooltipCornerRadius: 2,
        barDatasetSpacing : 3,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].fillColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    
});