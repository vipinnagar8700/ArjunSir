<?php get_header();
/*
Template Name: Cryptocurrency
*/
?>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.js"></script>
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


	.green{
		color:green !important;
	}

    

 #close-results {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translate(0px, -50%);
    cursor: pointer;
}
</style>
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
                        <span id="close-results"><img src="/wp-content/uploads/2024/05/close_button.svg" alt="close icon" class="img-fluid close_icons_"></span>
						</div>
                        <!-- <a href="/data/cryptocurrency/" id="showall" class="black">Show all</a> -->
                    </div>
                    <div id="search-results" class="search_result_"></div>
                </div>

                <!-- <div class="text-end mb-4">
                    <a href="" class="btn btn-primary ">Show All</a>
                </div> -->
                <div class="crypto_table mb-4" style="overflow-x: auto;">
                    <table class="table " id="table_crypto">
                        <thead>
                            <tr class="table-head">
                                <td>Rank</td>
                                <td>Cryptocurrency</td>
                                <td>Price</td>
                                <td>24h Price change</td>
                                <td>24h trading volume</td>
                                <td>Marketcap</td>
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
                                <h4 class="fw-bold mb-0 ">Total marketcap</h4>
                            </div>
                            <div class="mb-4  " id="total_marketcap" style="height: 300px; width: 100%;"></div>


                            <div class="text-end">
                                <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                            </div>


                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="charts_grid">
                            <div class="mb-3">
                                <h4 class="fw-bold mb-0 ">Total altcoins marketcap</h4>
                            </div>
                            <div class="mb-4  " id="total_altcoin" style="height: 300px; width: 100%;"></div>


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
                                <h4 class="fw-bold mb-0 ">Total market volume</h4>
                            </div>
                            <div class="mb-4  " id="total_market_volume" style="height: 300px; width: 100%;"></div>


                            <div class="text-end">
                                <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                            </div>


                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="charts_grid">
                            <div class="mb-3">
                                <h4 class="fw-bold mb-0 ">Cryptoassets</h4>
                            </div>
                            <div class="mb-4  " id="crypto_asset" style="height: 300px; width: 100%;"></div>


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
        
        const cryptoApiUrl = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=100&page=1&sparkline=false';
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
                        <tr onclick="window.location='/data/cryptocurrency-single?id=${coin.id}'">
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
                                        ` 
                                        <p class="mb-0"><i class="fa fa-caret-up green"></i> <span class="green">${priceChange24h.toFixed(2)}%</span></p>` :
                                        ` 
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
                    $('#table_crypto').DataTable();
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
        }

        function updateExchangeData() {
            $.ajax({
                url: exchangeApiUrl,
                type: options.method,
                headers: options.headers,
                success: function(data) {
                    data.sort((a, b) => b.trade_volume_24h_btc - a.trade_volume_24h_btc);
                    let tableBody = '';
                    data.slice(0, 10).forEach((exchange, index) => {
                        const volume24h = exchange.trade_volume_24h_btc || 0;
                        const numberOfCoins = exchange.coins || 0;
                        const numberOfPairs = exchange.pairs || 0;

                        tableBody += `
                        <tr>
                            <td><span class="crypto_rank">${exchange.trust_score_rank}</span></td>
                            <td><span>${exchange.name}</span></td>
                            <td>$${(volume24h * 1e6).toFixed(2)} M</td>
                            <td>${exchange.trust_score}</td>
                            <td><span>${numberOfCoins}</span></td>
                            <td><span>${numberOfPairs}</span></td>
                        </tr>
                    `;
                    });
                    $('#table_exchange tbody').html(tableBody);
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
        }

        // Update the data when the document is ready
        updateCryptoData();
        // updateExchangeData();
    });

 


    jQuery(document).ready(function() {
    jQuery('#search_coin').keyup(function() {
        console.log('click')
        let query = jQuery(this).val();
        if (query.length > 0) {
            jQuery.ajax({
                url: `https://api.coingecko.com/api/v3/search?query=${query}`,
                method: 'GET',
                success: function(data) {
                    let resultsContainer = jQuery('#search-results');
                    resultsContainer.empty();
                    jQuery.each(data.coins, function(index, coin) {
                        let link = jQuery('<a></a>').attr('href', `/data/cryptocurrency-single/?id=${coin.id}`).html(`<span>${coin.name}</span>`);
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

async function totalMarketcap(){
    $.ajax({
                url: 'https://api.coingecko.com/api/v3/global',
                method: 'GET',
                success: function(data) {
                    // Extract the total market cap data
                    let marketCapData = data.data.total_market_cap;
                    
                    // Prepare data for the chart
                    let dataPoints = [];
                    
                    // Assuming you want to show market cap in USD
                    let marketCapUSD = marketCapData.usd;
                    
                    dataPoints.push({ x: new Date(), y: marketCapUSD });
                    
                    // Create the chart
                    var chart = new CanvasJS.Chart("total_marketcap", {
                        animationEnabled: true,
                        theme: "light2",
                        title: {
                            text: null
                        },
                        axisX: {
                            valueFormatString: "DD MMM"
                        },
                        axisY: {
                            title: "Market Cap (USD)",
                            includeZero: false
                        },
                        data: [{
                            type: "line",
                            xValueType: "dateTime",
                            xValueFormatString: "DD MMM YYYY",
                            yValueFormatString: "$#,##0.##",
                            dataPoints: dataPoints
                        }]
                    });
                    chart.render();
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
}

async function totalAltcoin(){
                // Create initial data point array
                let dataPoints = [];

// Create the chart
let chart = new CanvasJS.Chart("total_altcoin", {
    animationEnabled: true,
    theme: "light2",
    title: {
        text: null
    },
    axisX: {
        valueFormatString: "DD MMM YYYY"
    },
    axisY: {
        title: "Market Cap (USD)",
        includeZero: false
    },
    toolTip: {
        shared: true
    },
    data: [{
        type: "splineArea",
        xValueType: "dateTime",
        xValueFormatString: "DD MMM YYYY",
        yValueFormatString: "$#,##0.##",
        dataPoints: dataPoints
    }]
});

// Function to fetch historical market cap data for Bitcoin
function fetchHistoricalData() {
    // Example: Fetch historical market cap data for Bitcoin from CoinGecko API
    $.ajax({
        url: 'https://api.coingecko.com/api/v3/coins/bitcoin/market_chart',
        method: 'GET',
        data: {
            vs_currency: 'usd',
            days: '365' // Fetch data for the last 365 days (adjust as needed)
        },
        success: function(response) {
            // Process historical data and add data points to the chart
            let prices = response.prices;
            prices.forEach(function(price) {
                let date = new Date(price[0]);
                let marketCap = price[1];
                dataPoints.push({ x: date, y: marketCap });
            });

            // Update chart with new data
            chart.render();
        },
        error: function(error) {
            console.error('Error fetching historical data:', error);
        }
    });
}

// Initial call to fetch historical data
fetchHistoricalData();
}

 
async function totalmarketVolume(){
            let dataPoints = [];
            let fetchedDataCount = 0;
            const requiredDataCount = 2;

            // Function to fetch historical market volume data for a given cryptocurrency
            function fetchHistoricalData(coinId) {
                $.ajax({
                    url: `https://api.coingecko.com/api/v3/coins/${coinId}/market_chart`,
                    method: 'GET',
                    data: {
                        vs_currency: 'usd',
                        days: '365'
                    },
                    success: function(response) {
                        let volumes = response.total_volumes;
                        volumes.forEach(function(volume, index) {
                            let date = new Date(volume[0]);
                            let marketVolume = volume[1];

                            if (!dataPoints[index]) {
                                dataPoints[index] = { x: date, y: 0 };
                            }

                            dataPoints[index].y += marketVolume;
                        });

                        fetchedDataCount++;
                        if (fetchedDataCount === requiredDataCount) {
                            var chart = new CanvasJS.Chart("total_market_volume", {
                                animationEnabled: true,
                                theme: "light2",
                                title: {
                                    text: null
                                },
                                axisX: {
                                    valueFormatString: "DD MMM YYYY"
                                },
                                axisY: {
                                    title: "Volume (USD)",
                                    includeZero: false
                                },
                                data: [{
                                    type: "line",
                                    xValueType: "dateTime",
                                    xValueFormatString: "DD MMM YYYY",
                                    yValueFormatString: "$#,##0.##",
                                    dataPoints: dataPoints
                                }]
                            });

                            chart.render();
                        }
                    },
                    error: function(error) {
                        console.error('Error fetching historical data for ' + coinId + ':', error);
                    }
                });
            }

            // Initial call to fetch historical data for multiple cryptocurrencies
            fetchHistoricalData('bitcoin');
            fetchHistoricalData('ethereum');
        }
        async function crypto_asset(){
             // IDs of cryptocurrencies to fetch (e.g., bitcoin, ethereum, ripple, etc.)
             let cryptoIds = ['bitcoin', 'ethereum', 'ripple','solana','tether'];

let promises = cryptoIds.map(id => {
    return $.ajax({
        url: `https://api.coingecko.com/api/v3/coins/${id}`,
        method: 'GET'
    });
});

$.when(...promises).then(function(...responses) {
    let dataPoints = [];

    // Process each response
    responses.forEach(response => {
        let data = response[0];
        let marketCapUSD = data.market_data.market_cap.usd;

        dataPoints.push({
            y: marketCapUSD,
            indexLabel: data.name + " - {y}",
            legendText: data.name
        });
    });

    // Create the chart
    var chart = new CanvasJS.Chart("crypto_asset", {
        animationEnabled: true,
        theme: "light2",
        title: {
            text: null
        },
        data: [{
            type: "pie",
            startAngle: 240,
            yValueFormatString: "$#,##0.##",
            indexLabel: "{label} {y}",
            dataPoints: dataPoints
        }]
    });

    chart.render();
}).fail(function(error) {
    console.error('Error fetching data from CoinGecko API:', error);
});
        }
setTimeout(() => {
      
         totalMarketcap();
         totalAltcoin();
         totalmarketVolume();
         crypto_asset();
        }, 1000);
</script>
<?php get_footer(); ?>