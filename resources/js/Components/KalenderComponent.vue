<script setup>
import { ref, computed } from "vue";
import dayjs from "dayjs";
import weekday from "dayjs/plugin/weekday";
import isoWeek from "dayjs/plugin/isoWeek";
import weekOfYear from "dayjs/plugin/weekOfYear";

dayjs.extend(weekday);
dayjs.extend(isoWeek);
dayjs.extend(weekOfYear);

const props = defineProps({
    year: Number,
    feiertage: Array,
    urlaubsTage: Array,
    veranstaltungenDatum: Array,
    homeoffice: Array,
    abwesenheit: Array,
    allEmployees: Array,
});

const emit = defineEmits(["showWeekModal", "showDayModal", "showMonthModal"]);

const months = Array.from({ length: 12 }, (_, i) => i + 1);

const getMonthName = (month) => {
    return dayjs().month(month - 1).locale("de").format("MMMM");
};

const getStartOfMonth = (month) => {
    return dayjs(`${props.year}-${month}-01`);
};

const getMonthDays = (month) => {
    const start = getStartOfMonth(month);
    const end = start.endOf("month");
    const days = [];

    let d = start;
    while (d.isBefore(end) || d.isSame(end, "day")) {
        days.push(d);
        d = d.add(1, "day"); // Sicherstellen, dass das neue Objekt nicht überschrieben wird.
    }
    return days;
};

const getDayColorClass = (day) => {
    const dateStr = day.format("YYYY-MM-DD");

    // if (props.feiertage.includes(dateStr) && dateStr !== "2023-11-22") {
    //     return "text-[#CC0000] font-bold";
    // }

    // const geburtsTagInfo = props.allEmployees.filter((i) =>
    //     dayjs(i.geburtsdatum).isSame(day, "day")
    // );
    // if (geburtsTagInfo.length) {
    //     return "text-[#a16207] font-bold";
    // }

    // const urlaubsStatus = props.urlaubsTage.find((i) => i.datum === dateStr && i.user_id === 1)?.status;

    // if (urlaubsStatus === "AN") return "text-purple-700 font-bold";
    // if (urlaubsStatus === "U") return props.veranstaltungenDatum.includes(dateStr) ? "text-[#046f47] font-bold" : "text-lime-500";
    //
    // if (props.veranstaltungenDatum.includes(dateStr)) return "text-[#87bdd8] font-bold";
    // if (props.homeoffice.some((i) => i.datum === dateStr && i.user_id === 1)) return "text-blue-600 font-bold";
    // if (props.abwesenheit.some((i) => i.datum === dateStr && i.user_id === 1)) return "text-yellow-400 font-bold";

    return "";
};
</script>

<template>
    <div class="text-center p-2 font-bold">
        <router-link :to="{ name: 'kalender', params: { year: year - 1 } }" class="hover:text-gray-400 px-2">
            ⬅️ Letztes Jahr
        </router-link>
        {{ year }}
        <router-link :to="{ name: 'kalender', params: { year: year + 1 } }" class="hover:text-gray-400 px-2">
            Nächstes Jahr ➡️
        </router-link>
    </div>

    <div class="flex flex-wrap justify-center">
        <div v-for="month in months" :key="month" class="grid grid-cols-8 grid-rows-7 gap-2 p-3 h-max">
            <div
                class="rounded text-white col-span-8 text-center bg-gray-700 p-1 cursor-pointer border border-gray-200 dark:bg-green-800 dark:border-gray-700 dark:text-white"
                @click="emit('showMonthModal', getStartOfMonth(month))"
            >
                {{ getMonthName(month) }}
            </div>

            <!-- Kalenderüberschrift -->
            <div class="text-sm text-center text-gray-400">KW</div>
            <div v-for="day in ['MO', 'DI', 'MI', 'DO', 'FR', 'SA', 'SO']" :key="day" class="text-sm text-center font-bold" :class="{'text-gray-400': day === 'SA' || day === 'SO'}">
                {{ day }}
            </div>

            <!-- Tage des Monats -->
            <template v-for="day in getMonthDays(month)" :key="day.format('YYYY-MM-DD')">
                <div
                    v-if="day.date() === 1 || day.isoWeekday() === 1"
                    @click="emit('showWeekModal', day.format('YYYY-MM-DD'))"
                    class="text-gray-400 cursor-pointer"
                >
                    {{ day.week() }}
                </div>

                <div
                    :class="[
                        day.date() === 1 ? 'col-start-' + (day.isoWeekday() + 1) : '',
                        'cursor-pointer border-transparent border-2 h-6 hover:border-black dark:hover:border-gray-100',
                        day.isoWeekday() >= 6 ? 'text-gray-400' : '',
                        getDayColorClass(day),
                        day.isSame(dayjs(), 'day') ? 'border-2 border-black font-bold' : ''
                    ]"
                    @click="emit('showDayModal', day.format('YYYY-MM-DD'))"
                >
                    {{ day.format("D") }}
                </div>
            </template>
        </div>
    </div>
</template>
