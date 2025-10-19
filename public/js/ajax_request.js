$(document).ready(function () {
    const startSelect = $("#start");
    const barberSelect = $("#barber");
    const timeSelect = $("#startTime");
    const timeContainer = $("#startTimeDiv");

    timeContainer.hide();

    function renderTimes(times) {
        timeSelect.empty();

        if (!times.length) {
            timeSelect.append(
                $("<option />")
                    .prop("disabled", true)
                    .text("Нет доступного времени")
            );
            timeContainer.show();
            return;
        }

        timeSelect.append(
            $("<option />")
                .prop("disabled", true)
                .prop("selected", true)
                .text("Выберите время")
        );

        $.each(times, function (_, value) {
            timeSelect.append(
                $("<option />")
                    .val(value)
                    .text(value)
            );
        });

        timeContainer.show();
    }

    function fetchTimes() {
        const date = startSelect.val();
        const barber = barberSelect.val();

        if (!date || !barber) {
            timeContainer.hide();
            return;
        }

        $.ajax({
            type: "get",
            url: "available-times",
            data: { date: date, barber: barber },
            success: function (data) {
                renderTimes(Array.isArray(data) ? data : []);
            },
            error: function () {
                renderTimes([]);
            },
        });
    }

    startSelect.on("change", fetchTimes);
    barberSelect.on("change", fetchTimes);

    fetchTimes();
});
