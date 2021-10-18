// generates span tag which is going to be a circle
function animations_init() {
    let animCircle = document.createElement("span");
    
    animCircle.className = "anim-circle";
    document.body.appendChild(animCircle);
    
    // set circle diamater 2R = height * sqrt(ratio^2 + 1)
    const aspectRatio = getComputedStyle(animCircle).getPropertyValue("--aspect-ratio");
    let circle_radius = Math.ceil(100 * Math.sqrt(aspectRatio * aspectRatio + 1.1));

    animCircle.style.setProperty("--circle-radius", circle_radius + "vh");    
    animCircle.style.width = "let(--circle-radius)";
    animCircle.style.height = "let(--circle-radius)";
    
    //console.log(`Aspect ratio: ${aspectRatio}`);
    //console.log(`Circle radius: ${circle_radius}vh`);    
}


// animate:

function animateCircleExpanding() {
    let animation_duration = 0.85;
    let animation_ease = "sine";

    let span_tag = document.getElementsByClassName("anim-circle")[0];

    gsap.fromTo(span_tag, {scale: 0, xPercent:-50, yPercent:-50, visibility: "visible"},
                          {scale: 1,  duration: animation_duration, ease: animation_ease});

    return animation_duration;
}


function animateCircleShrinking() {
    let animation_duration = 1.9;
    let animation_ease = "circ";

    let span_tag = document.getElementsByClassName("anim-circle")[0];
    span_tag.style.display = "visible";

    gsap.fromTo(span_tag, {visibility: "visible", xPercent:-50, yPercent:-50, scale: 1},
                          {scale: 0, duration: animation_duration, ease: animation_ease});

    return animation_duration;
}


class TextAnimation {
    text_tag;
    original_text;
    start_char_idx = -1;
    end_char_idx = -1;

    // takes in tag with text (no tags) whose chars of given range will be animated
    // end of range is exclusive
    constructor(text_tag_to_animate, start_char_idx, end_char_idx) {
        this.text_tag = text_tag_to_animate;
        // get all chars and wrap a span around them
        this.original_text = this.text_tag.textContent;
        this.text_tag.innerHTML = "";
        for (let i = 0; i < start_char_idx; ++i) {
            this.text_tag.innerHTML += this.original_text[i];
        }

        for (let i = start_char_idx; i < end_char_idx; ++i) {
            // this.text_tag.innerHTML += "<span>" + this.original_text[i] + "</span>";
            let cur_span = document.createElement("span");
            cur_span.style.cssText += "opacity: 0; font-size: 53px; ";
            if (this.original_text[i] !== " ") {
                cur_span.style.cssText += "transform: translateY(50px); transition: all 0.4s ease; display: inline-block;";
            }
            cur_span.innerHTML = this.original_text[i];
            this.text_tag.appendChild(cur_span);
        }

        // add the rest out of the given range
        this.text_tag.innerHTML += this.original_text.substring(end_char_idx);

        this.start_char_idx = start_char_idx;
        this.end_char_idx = end_char_idx;
    }

    // performs the animation in given duration in seconds
    startAnimation(duration) {
        if (!this.text_tag) {
            console.log("Text tag was not set for the TextAnimation");
            return;
        }
        const all_spans = this.text_tag.querySelectorAll("span");
        // in milliseconds:
        const offset = Math.floor(1000. * (duration - 0.4) / (this.end_char_idx - this.start_char_idx));
        for (let i = 0; i < this.end_char_idx - this.start_char_idx; ++i) {
            setTimeout(() => {
                console.log(`Adding span:${all_spans[i]}`);
                this.onTick(all_spans[i]);
            }, i * offset);
        }

        setTimeout(() => {
            this.onComplete(this.text_tag, this.original_text);
        }, 1000 * duration);
    }

    onTick(cur_span) {
        cur_span.classList.add("fade");
        cur_span.style.cssText += "opacity: 1; color: rgb(100 142 240);";
        if (cur_span.innerHTML !== " ") {
            cur_span.style.cssText += "transform: translateY(0px);";
        }
    }

    // kill the set interval and return the given tag to its original state
    onComplete(text_tag_, old_text) {
        text_tag_.innerHTML = old_text;
    }
}


// duration in seconds
function animateWinnerText(winner_name, duration) {
    // generate congratulations text:
    let winner_text_tag = document.createElement("h1");
    winner_text_tag.innerHTML = "Winner was " + winner_name;
    winner_text_tag.className = "winner-text";
    winner_text_tag.style.cssText += 'font-size: 45px; color: rgb(100 142 240); top: 53vh; left: 40vw;';

    document.documentElement.appendChild(winner_text_tag);

    let start_len = "Winner was ".length;
    let animation = new TextAnimation(winner_text_tag,
                                      start_len, start_len + winner_name.length);
    animation.startAnimation(duration);

    setTimeout(function() {
        document.documentElement.removeChild(winner_text_tag);
    }, 1000 * duration);
}


// shrinks the card as it is travelling and rotating from postion of given card to
// the given coordinate, end_coo are relative to x and y of the edges of rect of the tag
function animateCardTravelling(end_coo, animation_duration, card_to_cpy_and_animate) {
    let animation_ease = "sine";

    let starting_coo = [card_to_cpy_and_animate.getBoundingClientRect()["left"],
                        card_to_cpy_and_animate.getBoundingClientRect()["top"]];
    let scale = [card_to_cpy_and_animate.clientWidth, card_to_cpy_and_animate.clientHeight];
    // distances to travel in each direction to ending coo. relative to cur obj size
    // let distances_xy = [Math.floor(end_coo[0] - starting_coo[0]),
    //                     Math.floor(end_coo[1] - starting_coo[1]) - 50];  // 50px mistake in height
    //console.log(`Starting coo: ${starting_coo}\nScale: ${scale}\nDist: ${distances_xy}`);

    // generate the card which is going to be a part of animation
    // <img src="./imgs/card_back_round.svg">
    let card_object = document.createElement("img");
    card_object.setAttribute("src", card_to_cpy_and_animate.getAttribute("src"));
    card_object.setAttribute("style", `position: absolute;`);
    document.body.appendChild(card_object);

    // handle object sizes and distances

    // gsap.set(card_object, {x: starting_coo[0], y: starting_coo[1],
    //                        transformOrigin: "50% 50%", height: scale[1], width: scale[0]});
    gsap.set(card_object, {transformOrigin: "50% 50%", height: scale[1], width: scale[0]});
    card_object.style.cssText += `left: ${starting_coo[0]}px; top: ${starting_coo[1]}px;`;
    gsap.to(card_object, {left: `${end_coo[0]}px`, top: `${end_coo[1]}px`, rotation: 360,
                          scale: 0.4, duration: animation_duration, ease: animation_ease});

    // leave the card in its place for 0.35 seconds and then remove it from body
    setTimeout(function() {
        document.body.removeChild(card_object);
    }, 350 + animation_duration * 1000);
}
