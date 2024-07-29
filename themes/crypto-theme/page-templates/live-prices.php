<?php get_header();
/*
Template Name: Live Prices
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
 
 
        .tabs {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .tab {
            cursor: pointer;
            padding: 10px 20px;
            /* border: 1px solid #2E8B57; */
            border-radius: 5px;
            background-color: #1a1a40;
            color: white;
            transition: background-color 0.3s ease;
        }
        .tab:hover {
            background-color: #5642f5;
        }
        .active-tab{
         background-color: #5642F5;
        }
        #loading {
    position: absolute;
    display: none;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    text-align: center;
    opacity: 0.7;
    background-color: #8b8b9e;
    z-index: 99;
}

#loading-image {
    margin: auto;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 50px;
}
.live_price_chart {
 
    position: relative;
}
    </style>
      <!-- hype loot section  -->
      <section class="hype_loot">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="d-flex justify-content-center align-items-center gap-2 ">
                     <div><img src="<?php echo get_template_directory_uri(); ?>/images/hypeloot-full.png" class="img-fluid hype_loots " alt=""></div>
                     <div>
                        <p class="m-0 "> Join the First-Ever AI Powered Casino and Get Profits Daily! 💰</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- hype loot section end -->
      <!-- hot section  -->
      <section class="hots py-5">
         <div class="container">
            <div class="row pb-5">
               <div class="col-md-12">
                  <h2 class="global_heading"><span class="yellow">Live</span> Price</h2>
                  <div class="what_are">
                     
                     <div class="search-container">
                        <div class="wrap_search_filter">
                        <input type="search" id="search_coin" autocomplete="off" placeholder="What are you looking for?" class="what_are_search">
							<!-- <input type="text" id="search_coin" autocomplete="off" placeholder="What are you looking for?"> -->
                        <span id="close-results"><img src="/wp-content/uploads/2024/05/close_button.svg" alt="close icon" class="img-fluid close_icons_"></span>
						</div>
                        <!-- <a href="/data/cryptocurrency/" id="showall" class="black">Show all</a> -->
                    </div>
                    <div id="search-results" class="search_result_"></div>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-9 mb-25">
               <div class="live_price_chart">
               <div id="loading">
  <img id="loading-image" src="<?php echo site_url('wp-content/uploads/2024/06/loader.webp'); ?>" alt="Loading..." />
</div>
    <div class="d-flex justify-content-between align-items-center coin_row mb-5">
        <div class="chart_data d-flex align-items-center gap-3">
            <div id="coinImageContainer"><img src="" class="img-fluid coin_logo" alt="Coin Image"></div>
            <div class="coin-data">
                <div class="d-flex gap-2 align-items-center">
                    <p id="coinSymbol" class="m-0 fs-20 fw-bold gray">BTC</p>
                    <p class="black m-0">rank <span id="coinRank" class="round_badge ms-2">1</span></p>
                </div>
                <p id="coinName" class="fs-20 fw-bold black mb-0">Bitcoin</p>
            </div>
            <div class="d-flex gap-3">
                <p id="coinPrice" class="fs-20 fw-bold black mb-0">$62,480</p>
                <p id="coinChange" class="m-0 fs-14 yellow"><i class="bi bi-caret-up-fill pe-1"></i>1.16511%</p>
            </div>
        </div>
    </div>
    <div class="hours_row row mb-5">
        <div class="tabs">
            <div class="tab active-tab" data-period="1">24 hours</div>
            <div class="tab" data-period="7">7 days</div>
            <div class="tab" data-period="14">14 days</div>
            <div class="tab" data-period="30">30 days</div>
            <div class="tab" data-period="365">1 year</div>
        </div>
    </div>
    <div id="chartContainer" style="height: 300px; width: 100%;"></div>
</div>

                  <!-- paginations  -->
                  <!-- <nav aria-label="Page navigation example">
                     <ul class="pagination">
                        <li class="page-item">
                           <a class="page-link" href="#" aria-label="Previous">
                           <span aria-hidden="true">&laquo;</span>
                           </a>
                        </li>
                        <li class="page-item"><a class="page-link" href="#">1</a></li>
                        <li class="page-item active" aria-current="page">
                           <a class="page-link" href="#">2</a>
                        </li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                           <a class="page-link" href="#" aria-label="Next">
                           <span aria-hidden="true">&raquo;</span>
                           </a>
                        </li>
                     </ul>
                  </nav> -->
                  <!-- paginations end -->
               </div>
               <div class="col-md-3">
                  <div class="join_crypto mb-4">
                     <h4 class="white fw-bold mb-3">
                        Join the crypto
                        adventure
                     </h4>
                     <ul class="social_media mb-3 ms-0 p-0">
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-reddit"></i></a></li>
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-telegram"></i></a></li>
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-twitter"></i></a></li>
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-instagram"></i></a></li>
                     </ul>
                  </div>
                  <div class="hype_loot_box">
                     <div class="hype_loot_image text-center  ">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/hypeloot.gif" class="img-fluid " alt="">
                     </div>
                     <div class="hype_loot_content">
                        <p class="mb-0 fs-18 fw-bold ">Join the First-Ever AI Powered Casino and Get Profits Daily! 💰</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- hot section end -->
      <script>
             jQuery(document).ready(function() {
    jQuery('#search_coin').keyup(function() {
        
        let query = jQuery(this).val();
        if (query.length > 0) {
            jQuery.ajax({
                url: `https://api.coingecko.com/api/v3/search?query=${query}`,
                method: 'GET',
                success: function(data) {
                    let resultsContainer = jQuery('#search-results');
                    resultsContainer.empty();
                    jQuery.each(data.coins, function(index, coin) {
                        let link = jQuery(`<a onclick="showGraph('${coin.id}','${coin.name}')"></a>`).attr('href', `javascript:void(0)`).html(`<span>${coin.name}</span>`);
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
      </script>

<script>
      function showGraph(id,name) {
        $('#loading').show();
         $('#search_coin').val(name);
        
    const tabs = document.querySelectorAll('.tab');
    const coinSymbol = id; // Default coin symbol for Bitcoin

    // Function to fetch and render data based on the selected period
    function fetchChartData(period) {
        fetch(`https://api.coingecko.com/api/v3/coins/${coinSymbol}/market_chart?vs_currency=usd&days=${period}`)
            .then(response => response.json())
            .then(data => {
               
                let prices = data.prices.map(price => ({ x: new Date(price[0]), y: price[1] }));

                let chart = new CanvasJS.Chart("chartContainer", {
                    backgroundColor: "#fff",
                    animationEnabled: true,
                    title: {
                        text: null,
                        // text: `${name} Price Chart - Last ${period} ${period === "1" ? "Day" : "Days"}`,
                        fontColor: "#000"
                    },
                    axisX: {
                        valueFormatString: period > 1 ? "DD MMM" : "HH:mm",
                        labelFontColor: "#000"
                    },
                    axisY: {
                        title: "Price in USD",
                        labelFontColor: "#000",
                        titleFontColor: "#000",
                        prefix: "$"
                    },
                    data: [{
                        type: "area",
                        color: "#2E8B57",
                        dataPoints: prices
                    }]
                });

                chart.render();
                
                $('#loading').hide();
                jQuery('#search-results').empty();
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    // Function to fetch and display coin data
    function fetchCoinData() {
        fetch(`https://api.coingecko.com/api/v3/coins/${coinSymbol}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('coinImageContainer').innerHTML = `<img src="${data.image.small}" class="img-fluid coin_logo" alt="${data.name}">`;
                document.getElementById('coinSymbol').textContent = data.symbol.toUpperCase();
                document.getElementById('coinRank').textContent = data.market_cap_rank;
                document.getElementById('coinName').textContent = data.name;
                document.getElementById('coinPrice').textContent = `$${data.market_data.current_price.usd.toLocaleString()}`;
                let priceChangePercentage = data.market_data.price_change_percentage_24h;
                document.getElementById('coinChange').innerHTML = `
                    <i class="bi ${priceChangePercentage >= 0 ? 'bi-caret-up-fill' : 'bi-caret-down-fill'} pe-1"></i>
                    ${Math.abs(priceChangePercentage).toFixed(2)}%
                `;
                document.getElementById('coinChange').classList.toggle('yellow', priceChangePercentage >= 0);
                document.getElementById('coinChange').classList.toggle('red', priceChangePercentage < 0);
            })
            .catch(error => console.error('Error fetching coin data:', error));
    }

    // Event listener for tabs
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const period = this.getAttribute('data-period');
            tabs.forEach(t => t.classList.remove('active-tab'));
            this.classList.add('active-tab');
            fetchChartData(period);
        });
    });

    // Fetch and display data for the default period (24 hours) on initial load
    fetchChartData(1);
    fetchCoinData();
}
showGraph('bitcoin','Bitcoin');

    </script>
      <?php get_footer(); ?>