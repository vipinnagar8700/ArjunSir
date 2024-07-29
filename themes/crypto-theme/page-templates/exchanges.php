<?php get_header();
/*
Template Name: Exchanges
*/
?>
<style>
   .search_result_ {
    display: flex;
    flex-wrap: wrap;
    padding-top: 15px;
}
	.search_result_ a {
    background-color: #010314;
    display: inline-block;
    margin-right: 5px;
    margin-bottom: 5px;
    color: #fff;
    font-size: 15px;
    padding: 3px 10px;
    padding-bottom: 5px;
    border-radius: 5px;
}
	
	.search_result_ a:hover{
		background-color:#ffb900;
	}
	.wrap_search_filter{
		position:relative;
	}
 .close_icons_ {
    height: 15px;
}
    .search-container {
        position: relative;
    }

    #search_coin {
        width: 100%;
        padding: 10px;
    }


    

    

 #close-results {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translate(0px, -50%);
    cursor: pointer;
}
   

    
</style>
 <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.js"></script>
<?php
$ads = get_field('ads');
?>
<!-- hype loot section  -->
<section class="hype_loot text-center ">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
				 <a href="<?php echo $ads[0]['ads_url'];?>">
                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                    <div><img src="<?php echo $ads[0]['image'];?>" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[0]['text'];?></p>
                    </div>
                </div>
					 </a>
            </div>
        </div>
    </div>
</section>
<!-- hype loot section end -->



<section class="scroll_banner" style="background-image: url(<?php echo the_field('image');?>);">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h2 class="global_heading"><span class="yellow"><?php echo the_field('title');?></span></h2>
                <p class="mb-0 fs-20 white"><?php echo the_field('content');?></p>
            </div>
        </div>
    </div>
</section>



<!-- hot section  -->
<section class="hots py-5">
    <div class="container">

        <div class="row">
            <div class="col-lg-3 col-md-4 mb-25">
            <?php get_template_part('page-templates/components/page-sidebar'); ?>
            </div>
            <div class="col-lg-9 col-md-8 mb-25">
                <div class="what_are mb-4">
                    <div class="search-container">
						<div class="wrap_search_filter">
                        <input type="text" id="search_coin" autocomplete="off" placeholder="What are you looking for?">
							<span id="close-results"><img src="/wp-content/uploads/2024/05/close_button.svg" alt="close icon" class="img-fluid close_icons_"></span></div>
                        <!-- <a href="/data/cryptocurrency/" id="showall" class="black">Show all</a> -->
                    </div>
                    <div id="search-results" class="search_result_"></div>
                </div>

                <!-- <div class="text-end mb-4">
                    <a href="" class="btn btn-primary ">Show All</a>
                </div> -->
                <div class="crypto_table mb-4" style="overflow-x: auto;">
                <div class="d-inline-block d-md-flex  justify-content-between align-items-center mb-3 ">
                     <h5 class=" mb-0 mb-25">Top 25 Exchanges
                     </h5>
                     <!-- <div>
                        <a href="" class="btn btn-primary-small me-2 ">SPOT</a>
                        <a href="" class="btn btn-secondary-small ">DEX</a>
                     </div> -->
                  </div>
                    <table class="table " id="table_exchange">
                        <thead>
                        <tr class="table-head">
                                <td>Market</td>
                                <td>Year</td>
                                <td>24h Volume</td>
                                <td>Trust Score/Rank</td>
                              

                              </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>






                <div class="hypeloot_casino mb-4 text-center">
                    <div class="row">
                    <div class="col-md-12">
                            <a href="<?php echo $ads[1]['ads_url'];?>" class="white">
                                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2  ">
                                <div><img src="<?php echo $ads[1]['image'];?>" class="img-fluid bydefi " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[1]['text'];?></p>
                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>




                <div class="row charts_row mb-4">
                    <div class="col-md-12  mb-3">
                        <h4 class=" mb-0 mb-25 bold">Overview
                        </h4>
                    </div>
                    <div class="col-md-6">
                        <div class="charts_grid">
                            <div class="mb-3">
                                <h4 class="fw-bold mb-0 ">Exchanges volume</h4>
                            </div>
                            <div class="mb-4  " id="exchange_volume" style="height: 300px; width: 100%;"></div>


                            <div class="text-end">
                                <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                            </div>


                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="charts_grid">
                            <div class="mb-3">
                                <h4 class="fw-bold mb-0 ">Exchanges trading volume</h4>
                            </div>
                            <div class="mb-4  " id="exchange_trading_volume" style="height: 300px; width: 100%;"></div>


                            <div class="text-end">
                                <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                            </div>


                        </div>
                    </div>
                </div>



                <div class="hypeloot_casino mb-4 text-center">
                    <div class="row">
                    <div class="col-md-12">
                            <a href="<?php echo $ads[2]['ads_url'];?>" class="white">
                                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2  ">
                                <div><img src="<?php echo $ads[2]['image'];?>" class="img-fluid bydefi " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[2]['text'];?></p>
                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>



                <div class="row charts_row mb-4">
                    <div class="col-md-12  mb-3">
                        <h4 class=" mb-0 mb-25 bold">Overview
                        </h4>
                    </div>
                    <div class="col-md-6">
                        <div class="charts_grid">
                            <div class="mb-3">
                                <h4 class="fw-bold mb-0 ">Exchanges by volumes</h4>
                            </div>
                            <div class="mb-4  " id="exchange_by_volume" style="height: 300px; width: 100%;"></div>


                            <div class="text-end">
                                <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                            </div>


                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="charts_grid">
                            <div class="mb-3">
                                <h4 class="fw-bold mb-0 ">Exchanges by trades</h4>
                            </div>
                            <div class="mb-4  " id="exchange_by_trades" style="height: 300px; width: 100%;"></div>


                            <div class="text-end">
                                <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                            </div>


                        </div>
                    </div>
                </div>

 

            </div>
        </div>
    </div>
</section>
<!-- hot section end -->
<script>
    jQuery(document).ready(function($) {
        const cryptoApiUrl = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=11&page=1&sparkline=false';
        const exchangeApiUrl = 'https://api.coingecko.com/api/v3/exchanges';

        const options = {
            method: 'GET',
            headers: {
                accept: 'application/json',
                'x-cg-demo-api-key': 'CG-aJuTDgTp1nYxkH8hQ6EGrYjj'
            }
        };

        function updateCryptoData() {
            $.ajax({
                url: cryptoApiUrl,
                type: options.method,
                headers: options.headers,
                success: function(data) {
                    let tableBody = '';
                    data.forEach((coin, index) => {
                        const priceChange7d = coin.price_change_percentage_7d_in_currency || 0;
                        const priceChange24h = coin.price_change_percentage_24h || 0;
                        const totalVolume = coin.total_volume || 0;
                        const marketCap = coin.market_cap || 0;

                        tableBody += `
                        <tr onclick="window.location='/data/cryptocurrency/${coin.id}'">
                            <td><span class="crypto_rank">${index + 1}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <img src="${coin.image}" class="coin_img" alt="${coin.name}">
                                    <span class="coin_name fw-bold">${coin.name}</span>
                                    <span class="coin_symbol">${coin.symbol.toUpperCase()}</span>
                                </div>
                            </td>
                            <td><span class="coin-price-dp">$${coin.current_price}</span></td>
                            <td>
                                <div class="coin_price_range d-flex align-items-center gap-1">
                                    ${priceChange24h >= 0 ? 
                                        `<img src="https://crypto.rmcmgroup.in/wp-content/themes/crypto-theme/images/green-chart.svg" alt="" style="height: 25px;">
                                        <p class="mb-0"><i class="fa fa-caret-up green"></i> <span class="green">${priceChange24h.toFixed(2)}%</span></p>` :
                                        `<img src="https://crypto.rmcmgroup.in/wp-content/themes/crypto-theme/images/red-chart.svg" alt="" style="height: 25px;">
                                        <p class="mb-0"><i class="fa fa-caret-down red"></i> <span class="red">${priceChange24h.toFixed(2)}%</span></p>`
                                    }
                                </div>
                            </td>
                            <td><span>$${(totalVolume / 1e9).toFixed(2)} B</span></td>
                            <td><span>$${(marketCap / 1e12).toFixed(2)} T</span></td>
                        </tr>
                    `;
                    });
                    $('#table_crypto tbody').html(tableBody);
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
        }
        async function getUsdPrice() {
            return $.ajax({
                url: 'https://api.coingecko.com/api/v3/exchange_rates',
                type: 'GET'
            });
        }
        async function updateExchangeData() {
            const usdResponse = await getUsdPrice();
            const usdValue = usdResponse.rates.usd.value;
            $.ajax({
                url: exchangeApiUrl,
                type: options.method,
                headers: options.headers,
                success: function(data) {
					
                    data.sort((a, b) => b.trade_volume_24h_btc - a.trade_volume_24h_btc);
                    let tableBody = '';
                    data.slice(0, 25).forEach((exchange, index) => {
						volume24h = exchange.trade_volume_24h_btc * usdValue;
                        tableBody += `
                        <tr onclick="window.location='/data/exchanges-single?id=${exchange.id}'">
                           
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <img src="${exchange.image}" class="coin_img" alt="${exchange.name}" style="width: 50px; height: 50px;">
                                    <span class="coin_name fw-bold">${exchange.name}</span>
                                </div>
                            </td>
                             <td>${exchange.year_established}</td>
                            <td>$${volume24h}</td>
                            <td>${exchange.trust_score}/${exchange.trust_score_rank}</td>
                        </tr>
                        `;
                       
						
						
                    });
                    $('#table_exchange tbody').html(tableBody);
					$('#table_exchange').DataTable();
					
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
        }

        // Update the data when the document is ready
       // updateCryptoData();
         updateExchangeData();
    });

 


    jQuery(document).ready(function() {
    jQuery('#search_coin').keyup(function() {
        console.log('click')
        let query = jQuery(this).val();
        if (query.length > 0) {
            jQuery.ajax({
                url: `https://api.coingecko.com/api/v3/exchanges/list`,
                method: 'GET',
            success: function(data) {
                let resultsContainer = jQuery('#search-results');
                resultsContainer.empty();
                let filteredExchanges = data.filter(exchange => exchange.name.toLowerCase().includes(query.toLowerCase()));
                jQuery.each(filteredExchanges, function(index, exchange) {
                    let link = jQuery('<a></a>').attr('href', `/data/exchanges-single?id=${exchange.id}`).html(`<span>${exchange.name}</span>`);
                    resultsContainer.append(link);
                });
            }
        });
        } else {
            jQuery('#search-results').empty();
        }
    });

    jQuery('#close-results').click(function() {
        jQuery('#search-results').empty();
        jQuery('#search_coin').val('');
    });
});
async function exchange_trading_volume() {
    $.ajax({
        url: 'https://api.coingecko.com/api/v3/exchanges',
        method: 'GET',
        success: function(data) {
            // Filter the exchanges by the specified IDs
            const targetExchangeIds = ['binance', 'lbank', 'gate'];

            // Filter the exchanges to include only those with the specified IDs
            const filteredExchanges = data.filter(exchange => targetExchangeIds.includes(exchange.id));

            // Create data points for the filtered exchanges
            const dataPoints = filteredExchanges.map((exchange, index) => ({
                x: index + 1,
                y: exchange.trade_volume_24h_btc,
                label: exchange.name
            }));

            var options = {
                title: {
                    text: "Trading Volume of Selected Exchanges"
                },
                axisX: {
                    title: "Exchange",
                    interval: 1
                },
                axisY: {
                    title: "24h Trading Volume (BTC)",
                    includeZero: false
                },
                theme: "light2",
                animationEnabled: true,
                data: [{
                    type: "line",
                    toolTipContent: "<b>{label}</b>: {y} BTC",
                    showInLegend: true,
                    legendText: "{label}",
                    dataPoints: dataPoints,
                    markerType: "circle",  // Change the marker type to distinguish points
                    markerSize: 10,  // Marker size for better visibility
                    markerColor: "red",  // Marker color for better visibility
                    indexLabelFontSize: 16,
                    indexLabel: "{label} - {y} BTC",
                    indexLabelPlacement: "outside",
                    indexLabelFontColor: "transparent"  // Initially make the index label transparent
                }]
            };

            var chart = new CanvasJS.Chart("exchange_trading_volume", options);
            chart.render();
            
            // Event listener for hover
            $(".canvasjs-chart-container").on("mouseover", ".canvasjs-chart-tooltip", function() {
                $(".canvasjs-chart-tooltip").css("color", "black");
            });
            
            $(".canvasjs-chart-container").on("mouseout", ".canvasjs-chart-tooltip", function() {
                $(".canvasjs-chart-tooltip").css("color", "transparent");
            });
        },
        error: function(error) {
            console.log('Error fetching data:', error);
        }
    });
}
async function exchange_by_trades(){
    $.ajax({
        url: 'https://api.coingecko.com/api/v3/exchanges',
        method: 'GET',
        success: function(data) {
            // Filter the exchanges by the specified IDs
            const targetExchangeIds = ['binance', 'lbank', 'gate','gdax'];

            // Filter and map the relevant exchanges
            const filteredExchanges = data.filter(exchange => targetExchangeIds.includes(exchange.id));

            // Sort the filtered exchanges by 24h trading volume in descending order
            const sortedExchanges = filteredExchanges.sort((a, b) => b.trade_volume_24h_btc - a.trade_volume_24h_btc);
            
            // Select the top exchanges by volume (in this case all filtered ones)
            const topExchanges = sortedExchanges.slice(0, 5);

            // Calculate the total volume of the top exchanges
            const totalVolume = topExchanges.reduce((total, exchange) => total + exchange.trade_volume_24h_btc, 0);

            // Calculate the percentage of each exchange's volume relative to the total volume
            const dataPoints = topExchanges.map(exchange => ({
                y: (exchange.trade_volume_24h_btc / totalVolume) * 100,
                label: exchange.name
            }));

            var options = {
                title: {
                    text: null
                },
                subtitles: [{
                    text: null
                }],
                theme: "light2",
                animationEnabled: true,
                data: [{
                    type: "pie",
                    startAngle: 40,
                    toolTipContent: "<b>{label}</b>: {y}%",
                    showInLegend: true,
                    legendText: "{label}",
                    indexLabelFontSize: 16,
                    indexLabel: "{label} - {y}%",
                    dataPoints: dataPoints
                }]
            };

            var chart = new CanvasJS.Chart("exchange_by_trades", options);
            chart.render();
        },
        error: function(error) {
            console.log('Error fetching data:', error);
        }
    });
}
async function exchange_volume() {
    $.ajax({
        url: 'https://api.coingecko.com/api/v3/exchanges',
        method: 'GET',
        success: function(data) {
            // Sort the exchanges by 24h trading volume in descending order
            const sortedExchanges = data.sort((a, b) => b.trade_volume_24h_btc - a.trade_volume_24h_btc);
            
            // Select the top 5 exchanges by volume
            const topExchanges = sortedExchanges.slice(0, 5);

            const dataPoints = topExchanges.map((exchange, index) => ({
                x: index + 1,
                y: exchange.trade_volume_24h_btc,
                label: exchange.name
            }));

            var options = {
                title: {
                    text: null
                },
                axisX: {
                    title: "Exchange",
                    interval: 1
                },
                axisY: {
                    title: "24h Trading Volume (BTC)",
                    includeZero: false
                },
                theme: "light2",
                animationEnabled: true,
                data: [{
                    type: "line",
                    toolTipContent: "<b>{label}</b>: {y} BTC",
                    showInLegend: true,
                    legendText: "{label}",
                    dataPoints: dataPoints,
                    markerType: "circle",  // Change the marker type to distinguish points
                    markerSize: 10,  // Marker size for better visibility
                    markerColor: "red",  // Marker color for better visibility
                    indexLabelFontSize: 16,
                    indexLabel: "{label} - {y} BTC",
                    indexLabelPlacement: "outside",
                    indexLabelFontColor: "transparent"  // Initially make the index label transparent
                }]
            };

            var chart = new CanvasJS.Chart("exchange_volume", options);
            chart.render();
        },
        error: function(error) {
            console.log('Error fetching data:', error);
        }
    });
}
async function exchange_by_volume(){
    $.ajax({
    url: 'https://api.coingecko.com/api/v3/exchanges',
    method: 'GET',
    success: function(data) {
        // Sort the exchanges by 24h trading volume in descending order
        const sortedExchanges = data.sort((a, b) => b.trade_volume_24h_btc - a.trade_volume_24h_btc);
        
        // Select the top 5 exchanges by volume
        const topExchanges = sortedExchanges.slice(0, 5);

        const dataPoints = topExchanges.map(exchange => ({
            y: exchange.trade_volume_24h_btc,
            label: exchange.name
        }));

        var options = {
            title: {
                text: null
            },
            subtitles: [{
                text: null
            }],
            theme: "light2",
            animationEnabled: true,
            data: [{
                type: "pie",
                startAngle: 40,
                toolTipContent: "<b>{label}</b>: {y} BTC",
                showInLegend: true,
                legendText: "{label}",
                indexLabelFontSize: 16,
                indexLabel: "{label} - {y} BTC",
                dataPoints: dataPoints
            }]
        };

        var chart = new CanvasJS.Chart("exchange_by_volume", options);
        chart.render();
    },
    error: function(error) {
        console.log('Error fetching data:', error);
    }
});

}

setTimeout(() => {
 
    exchange_trading_volume();
    exchange_volume();
    exchange_by_trades();
    exchange_by_volume();
     }, 1000);
</script>
<?php get_footer(); ?>