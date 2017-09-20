import Vue from 'vue';

export default {
    load(pageComponent) {
        let divApp = document.createElement('div');
        divApp.id = 'slimvue-app';
        divApp.innerHTML = "<slimvue-page></slimvue-page>";
        document.body.appendChild(divApp);

        return window.slimvue = new Vue({
            components : {
                'slimvue-page' : pageComponent,
            },
            el         : "#slimvue-app",
        });
    },
}
