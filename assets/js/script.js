let $ = document

const menu = $.querySelector(".menu"),
      navbar = $.querySelector(".navbar"),
      navlistMobile = $.querySelector('.nav-list-mobile'),
      mainNavLink = $.querySelectorAll('.main-nav-link')

const lightLogo = $.querySelector('.light_logo'),
      darkLogo = $.querySelector('.dark_logo')
      darkLogoMobile = $.querySelector('.dark_logo_mobile')

const loaderElem = $.querySelector('.loader')

// Loading window ---
window.addEventListener('load',()=>{
  loaderElem.classList.add('hidden')
})

//Mobile-Navbar ---
if (menu && navlistMobile) {
  menu.addEventListener("click", () => {
    navlistMobile.style.opacity ="1"
    navlistMobile.classList.toggle("change");
  });
}

//--- Stickey-Menue ---
window.addEventListener("scroll", () => {
  if (!navbar) return
  if (window.pageYOffset > navbar.offsetTop) {
      navbar.classList.add("sticky");

      mainNavLink.forEach(item =>{
        item.style.color="#282828"

        item.addEventListener('mouseover', ()=>{
          item.style.color="#70A076"
        })
        item.addEventListener('mouseout', ()=>{
          item.style.color="#282828"
        })
      })
      if (matchMedia("(min-width: 768px)").matches) {
          darkLogo.style.display ="block"
          lightLogo.style.display ="none"
      }if (matchMedia("(min-width: 576px)").matches) {
        darkLogo.style.display ="block"
        // darkLogo.style.maxHeight ="5.75rem"
        lightLogo.style.display ="none"
    }
 } else {
      navbar.classList.remove("sticky");
      
      mainNavLink.forEach(item =>{
        item.style.color="#EEE"

        item.addEventListener('mouseover', ()=>{
          item.style.color="#EEE"
        })
        item.addEventListener('mouseout', ()=>{
          item.style.color="#EEE"
        })
      })
      
      if (matchMedia("(min-width: 576px)").matches) {
        lightLogo.style.display ="none"
        darkLogo.style.display ="block"
      }
      if (matchMedia("(min-width: 768px)").matches) {
        lightLogo.style.display ="block"
        darkLogo.style.display ="none"
      }     
 }
  // --- Back_To_Top_Icon ---
  const whenWhereSection = $.querySelector('.when-where-section')
  const backToTopContainer = $.querySelector('.backtotop-container')

  if (whenWhereSection && backToTopContainer && window.pageYOffset > whenWhereSection.offsetTop) {
    backToTopContainer.classList.add('show')
  }else if (backToTopContainer) {
    backToTopContainer.classList.remove('show')
  }
});

// --- Music_Website ---
const musicIcon = $.querySelector('.music-icon'),
      playerElem = $.querySelector('.player')
let   flagDisplay = true

if (musicIcon && playerElem) {
  musicIcon.addEventListener("click",()=>{
    if (flagDisplay) {
      playerElem.style.display = "block"
      flagDisplay = false
    }else{
      playerElem.style.display = "none"
      flagDisplay = true
    }
  })
}
// Music:
const title = $.getElementById("title"),
      artist = $.getElementById("artist"),
      music = $.querySelector("audio"),
      currentTimeEl = $.getElementById("current-time"),
      durationEl = $.getElementById("duration"),
      progress = $.getElementById("progress"),
      progressContainer = $.getElementById("progress-container"),
      playBtn = $.getElementById("play"),
      background = $.getElementById("background");

const songs = [
  {
    path:
      "assets/media/Parson James - Waiting Game (Acoustic).mp3",
    displayName: "Waiting Game",
    artist: "Parson James",
    cover:
      "assets/images/Capture.JPG",
  },
];

let isPlaying = false;

function playSong() {
  isPlaying = true;
  playBtn.classList.replace("fa-play", "fa-pause");
  playBtn.setAttribute("title", "Pause");
  music.play();
}

function pauseSong() {
  isPlaying = false;
  playBtn.classList.replace("fa-pause", "fa-play");
  playBtn.setAttribute("title", "Play");
  music.pause();
}

if (playBtn && music) {
  playBtn.addEventListener("click", function () {
    if (isPlaying) {
      pauseSong()
    } else {
      playSong()
    }
  })
}
function loadSong(song) {
  title.textContent = song.displayName;
  artist.textContent = song.artist;
  music.src = song.path;
}
let songIndex = 0;

function prevSong() {
  songIndex--;
  if (songIndex < 0) {
    songIndex = songs.length - 1;
  }
  loadSong(songs[songIndex]);
  playSong();
}

function nextSong() {
  songIndex++;
  if (songIndex > songs.length - 1) {
    songIndex = 0;
  }
  loadSong(songs[songIndex]);
  playSong();
}

if (title && artist && music) {
  loadSong(songs[songIndex]);
}

function updateProgressBar(e) {
  if (isPlaying) {
    const duration = e.srcElement.duration;
    const currentTime = e.srcElement.currentTime;
    const progressPercent = (currentTime / duration) * 100;
    progress.style.width = progressPercent + "%";
    const durationMinutes = Math.floor(duration / 60);
    let durationSeconds = Math.floor(duration % 60);
    if (durationSeconds < 10) {
      durationSeconds = "0" + durationSeconds;
    }
    if (durationSeconds) {
      durationEl.textContent = durationMinutes + ":" + durationSeconds;
    }
    const currentMinutes = Math.floor(currentTime / 60);
    let currentSeconds = Math.floor(currentTime % 60);
    if (currentSeconds < 10) {
      currentSeconds = "0" + currentSeconds;
    }
    currentTimeEl.textContent = currentMinutes + ":" + currentSeconds;
  }
}
function setProgressBar(e) {
  const width = this.clientWidth;
  const clickX = e.offsetX;
  const duration = music.duration;
  music.currentTime = (clickX / width) * duration;
}

if (music) {
  music.addEventListener("ended", nextSong);
  music.addEventListener("timeupdate", updateProgressBar);
}
if (progressContainer) {
  progressContainer.addEventListener("click", setProgressBar);
}

// --- banner-slider-header- --
let lis = document.querySelectorAll(".liElem"),
    circle = document.querySelectorAll(".circle"),
    num = 1

if (circle.length && lis.length) {
  circle[0].style.backgroundColor = "transparent"
  lis[0].style.display = "block";
}

let classli = 0;
circle.forEach(function(x){
  x.setAttribute("num", classli);
  classli++;
})
circle.forEach(function(x){
  x.addEventListener("click", function(){
    let num = x.getAttribute("num");
    clearInterval(inte);
    lis.forEach(function(x){
      x.style.display = "none";
    });
    circle.forEach(function(x){
      x.style.backgroundColor = "#fff";
    });
    lis[num].style.display = "block";
    circle[num].style.backgroundColor = "#70A076"
  })
})
let inte = setInterval(function(){
  if (!lis.length || !circle.length) return
  if(num == lis.length){
    num = 0;
  }
  lis.forEach(function(x){
    x.style.display = "none";
  });
  circle.forEach(function(x){
    x.style.backgroundColor = "#9e9a9a";
  })
  lis[num].style.display = "block";

  circle[num].style.backgroundColor = "#70A076"
  num++;
},6000);   //3000

// --- CountDown Wedding ---
let endDate = new Date("Jan 20, 2023 00:00:00").getTime();
let x = setInterval(function() {
    const dayElem = $.querySelector('.countdown-day')
    const hourElem = $.querySelector('.countdown-hour')
    const minuteElem = $.querySelector('.countdown-minute')
    const secondElem = $.querySelector('.countdown-second')
    const countContainer = $.getElementById("countContainer")
    if (!dayElem || !hourElem || !minuteElem || !secondElem || !countContainer) return
    let now = new Date().getTime();
    let distance = endDate - now;
    let days = Math.floor(distance / (1000 * 60 * 60 * 24));
    let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    let seconds = Math.floor((distance % (1000 * 60)) / 1000);
    dayElem.innerHTML = ("0" + days).slice(-2);  
    hourElem.innerHTML = ("0" + hours).slice(-2);
    minuteElem.innerHTML = ("0" + minutes).slice(-2);
    secondElem.innerHTML = ("0" + seconds).slice(-2);
    if (distance < 0) {
        clearInterval(x);
        countContainer.innerHTML = "EXPIRED";
    }
}, 1000);

 
/* Slider-best_Momets */
let slideIndexTow = 0;
if ($.getElementsByClassName("mySlides-bestMoments").length && $.getElementsByClassName("dot-bestMoments").length) {
  showSlidesBestMoment();
}

function showSlidesBestMoment() {
  let j,
      slidesTwo = $.getElementsByClassName("mySlides-bestMoments"),
      dotsTwo = $.getElementsByClassName("dot-bestMoments");

  for (j = 0; j < slidesTwo.length; j++) {
    slidesTwo[j].style.display = "none";  
  }
  slideIndexTow++;
  if (slideIndexTow > slidesTwo.length) {slideIndexTow = 1}    
  for (j = 0; j < dotsTwo.length; j++) {
    dotsTwo[j].className = dotsTwo[j].className.replace(" active-bestMoments", "");
  }
  slidesTwo[slideIndexTow-1].style.display = "block";  
  dotsTwo[slideIndexTow-1].className += " active-bestMoments";
  setTimeout(showSlidesBestMoment, 4000)
} 

// --- section-bestFriends _ Switch Form's Tabs
const groomsMenTab = document.querySelector('.groomsMen-tab'),
      brideMaidsTab = document.querySelector('.brideMaids-tab')

const groomsMenForm = document.querySelector('.groomsMen-form'),
      brideMaidsForm = document.querySelector('.brideMaids-form')

if (groomsMenTab && brideMaidsTab && groomsMenForm && brideMaidsForm) {
  groomsMenTab.addEventListener('click',e =>{
    groomsMenTab.style.borderBottomColor="#70A076"
    brideMaidsTab.style.borderBottomColor="transparent"
  
    brideMaidsForm.style.display = 'none'
    groomsMenForm.style.display = 'block'
    groomsMenForm.style.display = 'flex'
  })
  brideMaidsTab.addEventListener('click',e =>{
    groomsMenTab.style.borderBottomColor="transparent"
    brideMaidsTab.style.borderBottomColor="#70A076"
    brideMaidsForm.style.display = 'block'
    brideMaidsForm.style.display = 'flex'
    groomsMenForm.style.display    = 'none'
  })
}

// --- section textSlider ---
let containerTextSlider = $.querySelectorAll('.container-textSlider p'),
    counter = 0,
     old = 0

let sliderInterval = setInterval(() =>{
  old = counter -1
  if (counter == containerTextSlider.length) {
    counter=0
  }
    containerTextSlider[counter].style.display="block"
    counter+=1
    
    if (!(containerTextSlider[old] == undefined)) {
      containerTextSlider[old].style.display="none"
    }
},2000)

// --- EventSection-slider ---
let lisEvent = $.querySelectorAll(".liElem-event"),
    circleEvent = $.querySelectorAll(".circle-event"),
     numEvent = 1;

if (circleEvent.length && lisEvent.length) {
  circleEvent[0].style.backgroundColor = "transparent"
  lisEvent[0].style.display = "block";
}

let classliEvent = 0;
circleEvent.forEach(function(x){
    x.setAttribute("numEvent", classliEvent);
    classliEvent++;
})

circleEvent.forEach(function(x){
    x.addEventListener("click", function(){
        let numEvent = x.getAttribute("numEvent");
        clearInterval(inteEvent);
        lisEvent.forEach(function(x){
            x.style.display = "none";
        });
        circleEvent.forEach(function(x){
            x.style.backgroundColor = "#9e9a9a";
        });
        lisEvent[numEvent].style.display = "block";
        circleEvent[numEvent].style.backgroundColor = "#70A076"
    })
})
let inteEvent = setInterval(function(){
  if(numEvent == lisEvent.length){
      numEvent = 0;
  }
  lisEvent.forEach(function(x){
      x.style.display = "none";
  });
  circleEvent.forEach(function(x){
      x.style.backgroundColor = "#9e9a9a";
  })
  lisEvent[numEvent].style.display = "block";

  circleEvent[numEvent].style.backgroundColor = "#70A076"
  numEvent++;
},3000);   //3000

// next and previous button
let arrowLeft= $.querySelector('.fa-chevron-circle-left')
let arrowRight= $.querySelector('.fa-chevron-circle-right')

if (arrowRight) {
  arrowRight.addEventListener('click',()=>{
  // console.log("right",numEvent)
    clearInterval(inteEvent);
    lisEvent.forEach(function(x){
        x.style.display = "none";
    });
    circleEvent.forEach(function(x){
      x.style.backgroundColor = "#9e9a9a";
    });

    if(numEvent == 3){
      numEvent = 0;
      lisEvent[numEvent].style.display = "block";
      circleEvent[numEvent].style.backgroundColor = "#70A076"
      
    }else{
      lisEvent[numEvent].style.display = "block";
      circleEvent[numEvent].style.backgroundColor = "#70A076"
    }
    numEvent++
  })
}
if (arrowLeft) {
  arrowLeft.addEventListener('click',()=>{
  clearInterval(inteEvent);
  lisEvent.forEach(function(x){
      x.style.display = "none";
  });
  
  circleEvent.forEach(function(x){
  x.style.backgroundColor = "#9e9a9a";
  });
  numEvent--

  if(numEvent == -1){
      numEvent = 2;
      lisEvent[numEvent].style.display = "block";

      circleEvent[numEvent].style.backgroundColor = "#70A076"
    
  }else{
      lisEvent[numEvent].style.display = "block";
      circleEvent[numEvent].style.backgroundColor = "#70A076"
  }
  })
}

// (function (d) {
//   var w = d.documentElement.offsetWidth,
//       t = d.createTreeWalker(d.body, NodeFilter.SHOW_ELEMENT),
//       b;
//   while (t.nextNode()) {
//       b = t.currentNode.getBoundingClientRect();
//       if (b.right > w || b.left < 0) {
//           t.currentNode.style.setProperty('outline', '1px dotted red', 'important');
//           console.log(t.currentNode);
//       }
//   };
// }(document));

// --- Deixe um Recado ---
const messageForm = $.getElementById('message-form')
const senderNameInput = $.getElementById('sender-name')
const senderMessageInput = $.getElementById('sender-message')
const messageFeedback = $.getElementById('message-feedback')

if (messageForm) {
  messageForm.addEventListener('submit', event => {
    event.preventDefault()
    if (!messageFeedback) return

    const senderName = senderNameInput ? senderNameInput.value.trim() : ''
    const senderMessage = senderMessageInput ? senderMessageInput.value.trim() : ''

    if (!senderName) {
      messageFeedback.textContent = 'Por favor, informe o seu nome.'
      return
    }

    if (!senderMessage) {
      messageFeedback.textContent = 'Por favor, escreva uma mensagem.'
      return
    }

    const waMessage = encodeURIComponent(`Olá! Recebeu um novo recado.\nNome: ${senderName}\nMensagem: ${senderMessage}`)
    const waLink = `https://wa.me/244931405838?text=${waMessage}`

    messageFeedback.style.color = '#4e8a59'
    messageFeedback.textContent = 'Abrindo WhatsApp...'
    window.open(waLink, '_blank')
    messageForm.reset()
  })
}

// --- Lista de Presentes ---
const giftShopGrid = $.getElementById('gift-shop-grid')
const giftModalOverlay = $.getElementById('gift-modal-overlay')
const giftModalClose = $.getElementById('gift-modal-close')
const giftModalProduct = $.getElementById('gift-modal-product')
const giftTransferReference = $.getElementById('gift-transfer-reference')
const giftProofForm = $.getElementById('gift-proof-form')
const giftProofInput = $.getElementById('gift-proof')
const giftProofFeedback = $.getElementById('gift-proof-feedback')
const giftSenderNameInput = $.getElementById('gift-sender-name')
let currentGiftSelection = {
  product: 'Presente',
  price: '',
  reference: '0000'
}
const defaultGiftProducts = [



  { name: 'Exaustor telescópico, 60 cm', price: 291000, behavior: 'popup', image: 'assets/images/gifts/gift-1.svg' },
  { name: 'Placa a gás, 60 cm, Inox', price: 475000, behavior: 'popup', image: 'assets/images/gifts/gift-2.svg' },
  { name: 'Forno integrável, 60 x 60', price: 665000, behavior: 'popup', image: 'assets/images/gifts/gift-3.svg' },
  { name: 'Micro-ondas integrável', price: 484000, behavior: 'popup', image: 'assets/images/gifts/gift-4.svg' },
  { name: 'Congelador de instalação', price: 2087000, behavior: 'popup', image: 'assets/images/gifts/gift-5.svg' },
  { name: 'Frigorífico de instalação', price: 2087000, behavior: 'popup', image: 'assets/images/gifts/gift-6.svg' },
  { name: 'Máquina de Lavar Loiça', price: 957000, behavior: 'popup', image: 'assets/images/gifts/gift-7.svg' },
  { name: 'Máquina de Lavar Roupa', price: 957000, behavior: 'popup', image: 'assets/images/gifts/gift-8.svg' },
  { name: 'Moinho de café, Preto', price: 181870, behavior: 'popup', image: 'assets/images/gifts/gift-9.svg' },
  { name: 'Serie 6, Liquidificador', price: 541000, behavior: 'popup', image: 'assets/images/gifts/gift-10.svg' },

  { name: 'SAMSUNG | Crystal UHD 55', price: 642400, behavior: 'link', image: 'assets/images/gifts/gift-1.svg' },
  { name: 'MIDEA | Arca 142L', price: 195000, behavior: 'link', image: 'assets/images/gifts/gift-2.svg' },
  { name: 'MIDEA | AC SPLIT 9000Btu', price: 270500, behavior: 'link', image: 'assets/images/gifts/gift-3.svg' },
  { name: 'CLEA | Máquina de Pipoca', price: 20400, behavior: 'link', image: 'assets/images/gifts/gift-4.svg' },
  { name: 'Black + Decker | Air Fryer 4.5L', price: 97200, behavior: 'link', image: 'assets/images/gifts/gift-5.svg' },
  { name: 'Black + Decker | Picador de Alimentos', price: 34700, behavior: 'link', image: 'assets/images/gifts/gift-6.svg' },
  { name: 'ALMOFADA AREIA GRAVADA 45X45', price: 18745, behavior: 'link', image: 'assets/images/gifts/gift-7.svg' },
  { name: 'ALMOFADA COSTA DE LINHO 45X45', price: 14995, behavior: 'link', image: 'assets/images/gifts/gift-8.svg' },
  { name: 'BALDE CHAMPANHE INOX 184.99', price: 42900, behavior: 'link', image: 'assets/images/gifts/gift-9.svg' },
  { name: 'CAIXA CHAS ACACIA VIDRO 26,5X9X9 NATURAL', price: 24900, behavior: 'link', image: 'assets/images/gifts/gift-10.svg' },
  { name: 'DECANTADOR VIDRO 18,7X18,7X22 1200ML', price: 22900, behavior: 'link', image: 'assets/images/gifts/gift-1.svg' },
  { name: 'Aspirador sem saco 600w 2.4l preto', price: 325965, behavior: 'link', image: 'assets/images/gifts/gift-2.svg' },
  { name: 'TV 65" A PRO 2025', price: 925840, behavior: 'link', image: 'assets/images/gifts/gift-3.svg' },
  { name: 'Barra de Som Stage Air V2 2.0 Preto', price: 57135, behavior: 'link', image: 'assets/images/gifts/gift-4.svg' },
  { name: 'Balança WC MI smart S400', price: 29755, behavior: 'link', image: 'assets/images/gifts/gift-5.svg' },
  { name: 'Batedeira 375w Com Taça De 3L Branco', price: 78450, behavior: 'link', image: 'assets/images/gifts/gift-6.svg' },
  { name: 'Impressora Deskjet E-AIO 2976 ADV. (7.5) WIFI', price: 61815, behavior: 'link', image: 'assets/images/gifts/gift-7.svg' },
  { name: 'Arca Vertical 151L Branca', price: 320990, behavior: 'link', image: 'assets/images/gifts/gift-8.svg' },
  { name: 'Ar Condicionado 12000 Btu Split Inverter (In+Out)', price: 390990, behavior: 'link', image: 'assets/images/gifts/gift-9.svg' },
  { name: 'Frois', price: 32900, behavior: 'link', image: 'assets/images/gifts/gift-10.svg' },
  { name: 'Chaudry', price: 23800, behavior: 'link', image: 'assets/images/gifts/gift-1.svg' },
  { name: 'Neres', price: 36000, behavior: 'link', image: 'assets/images/gifts/gift-2.svg' }
]

const normalizeGiftProduct = product => {
  const siteUrl = String(product.siteUrl || product.url || product.link || '').trim()
  const behaviorRaw = String(product.behavior || '').trim().toLowerCase()
  const behavior = behaviorRaw === 'link' || (!behaviorRaw && siteUrl) ? 'link' : 'popup'

  return {
    ...product,
    behavior,
    siteUrl
  }
}

const giftProductsSource = Array.isArray(window.giftProductsConfig) && window.giftProductsConfig.length
  ? window.giftProductsConfig
  : defaultGiftProducts

const giftProducts = giftProductsSource.map(normalizeGiftProduct)

const formatGiftPrice = value => `${Number(value).toLocaleString('pt-PT')} Kz`
const escapeAttr = value => String(value).replace(/"/g, '&quot;')
const getGiftReferenceBaseCode = value => {
  const text = String(value || '')
  let hash = 0

  for (let index = 0; index < text.length; index += 1) {
    hash = (hash * 31 + text.charCodeAt(index)) % 9000
  }

  return String(hash + 1000).padStart(4, '0')
}

const giftReferenceByProduct = (() => {
  const usedCodes = new Set()
  const referenceMap = new Map()

  giftProducts.forEach((product, index) => {
    const name = String(product.name || `Produto-${index + 1}`)
    let codeNumber = Number(getGiftReferenceBaseCode(`${name}-${index}`))

    while (usedCodes.has(codeNumber)) {
      codeNumber = codeNumber >= 9999 ? 1000 : codeNumber + 1
    }

    usedCodes.add(codeNumber)
    referenceMap.set(name, String(codeNumber).padStart(4, '0'))
  })

  return referenceMap
})()

if (giftShopGrid) {
  const cardsMarkup = giftProducts.map((product, index) => {
    const buttonLabel = product.behavior === 'link' ? 'Ver no site' : 'Oferecer Presente'
    return `
    <article class="gift-shop-card">
      <img src="${escapeAttr(product.image || `assets/images/gifts/gift-${(index % 10) + 1}.svg`)}" alt="${escapeAttr(product.name)}">
      <div class="gift-shop-info">
        <h3>${product.name}</h3>
        <p class="gift-price">${formatGiftPrice(product.price)}</p>
        <button type="button" class="gift-offer-btn" data-behavior="${escapeAttr(product.behavior || 'popup')}" data-product="${escapeAttr(product.name)}" data-price="${formatGiftPrice(product.price)}" data-url="${escapeAttr(product.siteUrl || '')}" data-reference="${escapeAttr(giftReferenceByProduct.get(String(product.name || '')) || '0000')}">${buttonLabel}</button>
      </div>
    </article>
  `
  }).join('')
  giftShopGrid.innerHTML = cardsMarkup
}

const closeGiftModal = () => {
  if (!giftModalOverlay) return
  giftModalOverlay.classList.remove('active')
  giftModalOverlay.setAttribute('aria-hidden', 'true')
}

if (giftShopGrid && giftModalOverlay && giftModalProduct) {
  giftShopGrid.addEventListener('click', event => {
    const button = event.target.closest('.gift-offer-btn')
    if (!button) return
    const behavior = String(button.dataset.behavior || 'popup').trim().toLowerCase()

    if (behavior === 'link') {
      const productName = button.dataset.product || ''
      const configuredUrl = String(button.dataset.url || '').trim()
      const url = configuredUrl || `https://www.google.com/search?q=${encodeURIComponent(productName)}`
      window.open(url, '_blank')
      return
    }

    const product = button.dataset.product || 'Presente'
    const price = button.dataset.price || ''
    const referenceCode = button.dataset.reference || '0000'
    currentGiftSelection = {
      product,
      price,
      reference: referenceCode
    }
    giftModalProduct.innerHTML = `<p><strong>Produto:</strong> ${product}</p><p><strong>Valor:</strong> ${price}</p>`
    if (giftTransferReference) {
      giftTransferReference.textContent = `Presente de Casamento ${referenceCode}`
    }
    giftModalOverlay.classList.add('active')
    giftModalOverlay.setAttribute('aria-hidden', 'false')
  })
}

if (giftModalClose) {
  giftModalClose.addEventListener('click', closeGiftModal)
}

if (giftModalOverlay) {
  giftModalOverlay.addEventListener('click', event => {
    if (event.target === giftModalOverlay) closeGiftModal()
  })
}

if (giftProofForm && giftProofInput && giftProofFeedback) {
  giftProofForm.addEventListener('submit', async event => {
    event.preventDefault()
    const senderName = giftSenderNameInput ? giftSenderNameInput.value.trim() : ''
    const proofFile = giftProofInput.files && giftProofInput.files[0]

    if (!senderName) {
      giftProofFeedback.textContent = 'Informe o seu nome completo.'
      return
    }

    if (!proofFile) {
      giftProofFeedback.textContent = 'Submeta um comprovativo em PDF.'
      return
    }

    if (proofFile.type !== 'application/pdf') {
      giftProofFeedback.textContent = 'Apenas ficheiros PDF são permitidos.'
      return
    }

    if (proofFile.size > 1024 * 1024) {
      giftProofFeedback.textContent = 'O ficheiro deve ter no máximo 1MB.'
      return
    }

    try {
      const formData = new FormData()
      formData.append('sender_name', senderName)
      formData.append('product_name', currentGiftSelection.product)
      formData.append('product_price', currentGiftSelection.price)
      formData.append('product_reference', currentGiftSelection.reference)
      formData.append('gift_proof', proofFile)

      giftProofFeedback.style.color = '#4e8a59'
      giftProofFeedback.textContent = 'A enviar comprovativo...'

      const response = await fetch('send-gift-proof.php', {
        method: 'POST',
        body: formData
      })
      const result = await response.json()

      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'Falha ao enviar comprovativo.')
      }

      giftProofFeedback.style.color = '#4e8a59'
      giftProofFeedback.textContent = result.message || 'Comprovativo enviado com sucesso.'
      giftProofForm.reset()
      setTimeout(closeGiftModal, 1500)
    } catch (error) {
      giftProofFeedback.style.color = '#b42318'
      giftProofFeedback.textContent = error.message || 'Erro ao enviar comprovativo. Tente novamente.'
    }
  })
}
