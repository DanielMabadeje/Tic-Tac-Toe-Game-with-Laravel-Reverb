<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {useGameState, gameStates} from "@/stores/useGameState.js";
// import Echo from 'laravel-echo';
import { ref, computed, onUnmounted } from 'vue';
const props = defineProps(['game'])

const   boardState      =   ref(props.game.state ?? [0, 0,  0,  0,  0,  0,  0,  0,  0]);
const   players         =   ref([]);
const   gameState       =   useGameState();
const   page            =   usePage();

const lines =   [

    // rows
    [0, 1,  2],
    [3, 4,  5],
    [6, 7,  8],

    // columns
    [0, 3,  6],
    [1, 4,  7],
    [2, 5,  8],

    // diagonals
    [0, 4,  8],
    [2, 4,  6],
]

const fillSquare    =   (index) =>  {

    if (! yourTurn.value) {
        return;
    }

    boardState.value[index]   = xTurn.value ?  -1   :   1;
    router.put(route('games.update', props.game.id), {
        state: boardState.value,
    })

    checkForVictory();

}

const checkForVictory   =   ()  =>  {
    const winningLine   =   lines.map((line)    =>  line.reduce((carry, index)      =>  carry   +   boardState.value[index], 0))
            .find((sum) =>  Math.abs(sum)   ===  3)

    if (winningLine ===   -3) {
        gameState.change(gameStates.XWins)
        // alert("X has Won");
        Swal.fire({
            icon: "info",
            title: "Yay!!",
            text: "X Has won",
            footer: '<a href="#">Why do I have this issue?</a>'
        }).then((result)    =>  {
            resetGame()
        });

        return
    }

    if (winningLine ===  3) {
        gameState.change(gameStates.OWins)
        alert("0 has won");
        Swal.fire({
            icon: "info",
            title: "Yay!!",
            text: "O has won",
            footer: '<a href="#">Why do I have this issue?</a>'
        }).then((result)    =>  {
            resetGame()
        });
        return
    }

    if (!boardState.value.includes(0)) {
        gameState.change(gameStates.StaleMate)
        alert('Stale Mate');
        Swal.fire({
            icon: "info",
            title: "Oops...",
            text: "Something went wrong!",
            footer: '<a href="#">Why do I have this issue?</a>'
        }).then((result)    =>  {
            resetGame()
        });
        return;
    }
}

const resetGame =   ()  => {
    boardState.value      =   [0, 0,  0,  0,  0,  0,  0,  0,  0];
    gameState.change(gameStates.inProgress)

    router.put(route('games.update', props.game.id), {
        state: boardState.value,
    })
}

const xTurn =   computed(() =>  boardState.value.reduce((carry, value)   =>  carry   +   value, 0) ===0);
const yourTurn = computed(() => {
    if (props.game.player_one_id === page.props.auth.user.id) {
        return xTurn.value;
    }

    return ! xTurn.value;
})

Echo.join(`games.${props.game.id}`)
    .here((users)   =>  players.value   =   users)
    .joining((user) =>  router.reload({
        onSuccess:  ()  =>  players.value.push(user)
    }))
    .leaving((user) =>  players.value   =   players.value.filter(({id})  =>  id  !== user.id))
    .listen('PlayerMadeMove', ({game}) => {
        boardState.value    =   game.state;
        checkForVictory()
    });

onUnmounted(()  =>  {
    Echo.leave(`games.${props.game.id}`)
});
</script>

<template>
    <AuthenticatedLayout>
        <!-- {{game}} -->

        <menu class="grid grid-cols-3 gap-1.5 w-0 min-w-fit mx-auto mt-12">
            <li v-for="(square, index) in boardState" class="bg-gray-300 size-32 grid place-items-center" >
                <button @click="fillSquare(index)" v-if="square    === 0" class="place-self-stretch bg-gray-200 hover:bg-gray-300 transition-colors"></button>
                <span   v-else  v-text="square  === -1  ?   'X' : '0'" class="text-4xl font-bold"></span>
            </li>
        </menu>

        <ul class="max-x-sm mx-auto mt-6 space-y-2">
            <li class="flex items-center">
                <span  class="p-1.5 font-bold rounded bg-gray-200">X</span>
                <span>{{game.player_one.name}}</span>
                <span :class="{'!bg-green-500':   players.find(({id})  =>  id  ===   game.player_one_id)}"  class="bg-red-500 size-2 rounded-full"></span>
            </li>

            <li v-if="game.player_two" class="flex items-center">
                <span  class="p-1.5 font-bold rounded bg-gray-200">0</span>
                <span>{{game.player_two.name}}</span>
                <span :class="{'!bg-green-500':   players.find(({id})  =>  id  ===   game.player_one_id)}"  class="bg-red-500 size-2 rounded-full"></span>
            </li>

            <li v-else> Waiting For Player 2 ...</li>
        </ul>
    </AuthenticatedLayout>
</template>