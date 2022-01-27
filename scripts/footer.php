</div>
</div>
</div>
<script>
    var app = new Vue({
        el: '#app',
        data: {
            sidebarOpen: false,
            colorMode: 'light',
        },
        mounted() {
            if (localStorage.colorMode) {
                this.colorMode = localStorage.colorMode;
            }
        },
        watch: {
            colorMode(newName) {
                localStorage.colorMode = newName;
            },
        },
    })
</script>

</body>
</html>