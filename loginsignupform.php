<!-- Add animation styling -->
<link rel="stylesheet" type="text/css" href="./css/animations.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.2.6/gsap.min.js"></script>


<body data-barba="wrapper">

    <link rel="stylesheet" type="text/css" href="./css/loginsignupform_style.css">

    <div class="">
        <h2 class="z-depth-1 slogan">Let cards and online socialising meet</h2>
    </div>


    <div class="container" id="container">

        <!-- Sign up container -->
        <div class="form-container sign-up-container">
            <form action="includes/signup.inc.php" method="post">
                <h1>Create Account</h1>
                <span id="signupErrorStatus"></span>

                <input type="text" name="uid" placeholder="Username" maxlength="30">
                <input type="text" name="mail" placeholder="E-mail">
                <input type="password" name="pwd" placeholder="Password" maxlength="30">
                <input type="password" name="pwd-repeat" placeholder="Repeat Password" maxlength="30">

                <button type="submit" name="signup-submit" style="margin-top: 25px;">Sign up</button>
            </form>
        </div>

        <!-- Log in container -->
        <div class="form-container sign-in-container">
            <form action="includes/login.inc.php" method="post">
                <h1 style="margin-top: 17px; padding-bottom: 5px;">Log in</h1>
                <span id="loginErrorStatus"></span>

                <input type="text" name="mailuid" placeholder="Username" maxlength="30">
                <input type="password" name="pwd" placeholder="Password..." maxlength="30">

                <button type="submit" name="login-submit" style="margin-top: 25px;">Log in</button>
            </form>
        </div>

        <!-- Cards on the sides of forms -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To enter the lobby please log in</p>
                    <button class="ghost" id="signIn">Log In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Do not have an account?<br>Create one right now!</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>

    </div>  <!-- End of container -->


</body>


<!-- Add script for animation -->
<script src="./js/animations.js"></script>

<script>

    // get rid of the header:
    //document.getElementById("headerRow").style.display = "none";

    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('container');

    signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
    });

    signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
    });

    // Input fields:
    const loginUsernameInputField = document.getElementsByName("mailuid")[0];

    const signupUsernameInputField = document.getElementsByName("uid")[0];
    const signupEmailInputField = document.getElementsByName("mail")[0];

</script>
