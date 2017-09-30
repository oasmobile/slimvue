<template>
    <div id="slimvue">
        <img src="~assets/logo.png">
        <hello></hello>
        <div class="test">
            GOOD Day 3
        </div>
        <img src="/static/pic.png"/>
    </div>
</template>

<script>
    import Hello from 'components/Hello';

    export default {
        name       : 'app',
        components : {
            Hello,
        },
    };
</script>

<style type="scss">
    #slimvue {
        font-family             : 'Avenir', Helvetica, Arial, sans-serif;
        -webkit-font-smoothing  : antialiased;
        -moz-osx-font-smoothing : grayscale;
        text-align              : center;
        color                   : #2C3E50;
        margin-top              : 60px;

    }

    .test {
        background-image : url("~assets/logo.png");
    }
</style>
