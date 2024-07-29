// price charts 

window.onload = function () {

    var chart = new CanvasJS.Chart("price_chart", {
        animationEnabled: true,
       
        axisX: {
            minimum: new Date(2015, 01, 25),
            maximum: new Date(2017, 02, 15),
            valueFormatString: "MMM YY"
        },
        axisY: {
            title: "Number of Sales",
            titleFontColor: "#4F81BC",
            includeZero: true,
            suffix: "mn"
        },
        data: [{
            indexLabelFontColor: "darkSlateGray",
            name: "views",
            type: "area",
            yValueFormatString: "#,##0.0mn",
            dataPoints: [
                { x: new Date(2015, 02, 1), y: 74.4, label: "Q1-2015" },
                { x: new Date(2015, 05, 1), y: 61.1, label: "Q2-2015" },
                { x: new Date(2015, 08, 1), y: 47.0, label: "Q3-2015" },
                { x: new Date(2015, 11, 1), y: 48.0, label: "Q4-2015" },
                { x: new Date(2016, 02, 1), y: 74.8, label: "Q1-2016" },
                { x: new Date(2016, 05, 1), y: 51.1, label: "Q2-2016" },
                { x: new Date(2016, 08, 1), y: 40.4, label: "Q3-2016" },
                { x: new Date(2016, 11, 1), y: 45.5, label: "Q4-2016" },
                { x: new Date(2017, 02, 1), y: 78.3, label: "Q1-2017", indexLabel: "Highest", markerColor: "red" }
            ]
        }]
    });
    chart.render();
    
    }













    //rank chart  

        var chart = new CanvasJS.Chart("rank_chart", {
            animationEnabled: true,
          
            axisY: {
                title: "Satisfied Customers",
                includeZero: true,
                suffix: "%"
            },
            data: [{
                type: "stepArea",
                markerSize: 5,
                xValueFormatString: "YYYY",
                yValueFormatString: "#,##0.##\"%\"",
                dataPoints: [
                    { x: new Date(2010, 0), y: 34 },
                    { x: new Date(2011, 0), y: 73 },
                    { x: new Date(2012, 0), y: 78 },
                    { x: new Date(2013, 0), y: 82 },
                    { x: new Date(2014, 0), y: 70 },
                    { x: new Date(2015, 0), y: 86 },
                    { x: new Date(2016, 0), y: 80 }
                ]
            }]
        });
        chart.render();
        
    