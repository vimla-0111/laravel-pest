<!DOCTYPE html>
<html>

<head>
    <title>Popup on click of an element</title>
    <meta charset="UTF-8" />
    <!-- Cal element-click embed code begins -->
    <script type="text/javascript">
        (function(C, A, L) {
            let p = function(a, ar) {
                a.q.push(ar);
            };
            let d = C.document;
            C.Cal = C.Cal || function() {
                let cal = C.Cal;
                let ar = arguments;
                if (!cal.loaded) {
                    cal.ns = {};
                    cal.q = cal.q || [];
                    d.head.appendChild(d.createElement("script")).src = A;
                    cal.loaded = true;
                }
                if (ar[0] === L) {
                    const api = function() {
                        p(api, arguments);
                    };
                    const namespace = ar[1];
                    api.q = api.q || [];
                    if (typeof namespace === "string") {
                        cal.ns[namespace] = cal.ns[namespace] || api;
                        p(cal.ns[namespace], ar);
                        p(cal, ["initNamespace", namespace]);
                    } else p(cal, ar);
                    return;
                }
                p(cal, ar);
            };
        })(window, "https://app.cal.com/embed/embed.js", "init");
        Cal("init", "30min", {
            origin: "https://app.cal.com"
        });


        // Important: Please add the following attributes to the element that should trigger the calendar to open upon clicking.
        // `data-cal-link="vimla-zihnxr/30min"`
        // data-cal-namespace="30min"
        // `data-cal-config='{"layout":"month_view","useSlotsViewOnSmallScreen":"true"}'`

        Cal.ns["30min"]("ui", {
            "hideEventTypeDetails": false,
            "layout": "month_view"
        });
    </script>
    <!-- Cal element-click embed code ends -->
    <script>
        Cal("on", {
            action: "bookingSuccessfulV2",
            callback: (e) => {
                // `data` is properties for the event.
                // `type` is the name of the event(You can also call it type of the event.) This would be same as "ANY_EVENT_NAME" except when ANY_EVENT_NAME="*" which listens to all the events.
                // `namespace` tells you the Cal namespace for which the event is fired/
                const {
                    data,
                    type,
                    namespace
                } = e.detail;
                console.log(data, type);
                
            }
        });
    </script>
</head>

<body>
    <button data-cal-link="vimla-zihnxr/30min" data-cal-namespace="30min"
        data-cal-config='{"layout":"month_view","useSlotsViewOnSmallScreen":"true"}'>
        Book a Free consultation call
    </button>
</body>

</html>
