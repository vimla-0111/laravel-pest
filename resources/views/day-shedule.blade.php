<!DOCTYPE html>
<html lang="en">

<head>
    <title>Home</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width" />

    <!--Bootstrap library -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
    </script>

    <!--Dayschedule widget library from https://dayschedule.com/widget -->
    <script src="https://cdn.jsdelivr.net/npm/dayschedule-widget@latest/dist/dayschedule-widget.min.js" defer></script>
    <script defer>
        document.addEventListener('DOMContentLoaded', function() {
            // daySchedule.initPopupWidget({
            //     url: url,
            // });  

                let data = daySchedule.bookings.list({
                    start: "2023-10-11",
                    source: "dayschedule",
                    limit: 15,
                });
                console.log(data);
        });

        function openDaySchedule(url) {
            daySchedule.initPopupWidget({
                url: 'https://vimla.dayschedule.com/product-demo',
                // color: {
                //     primary: '#232325',
                //     secondary: '#adadad',
                // },
                // hideEvent: true,
                questions: {
                    name: "Vikash",
                    email: "Vikash.rathee@dayschedule.com",
                },
            });

            // window.location.href = 'https://vimla.dayschedule.com/book-appointment-with-admin';
        }
    </script>
</head>

<body>
    <div class="container px-3 my-5">
        <main>
            <button class="btn btn-primary" onclick="openDaySchedule()">
                Book appointment
            </button>
        </main>
    </div>
</body>

</html>
