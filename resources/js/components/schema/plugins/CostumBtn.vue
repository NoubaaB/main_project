<template>
		<v-lazy
            :options="{'threshold':0.5}"
            transition="fade-transition"
            v-show="main.command_drawer"
            >
			<div v-if="allow_slot" class="draggable" :class="myClass" :style="getPosition" >
				<slot name="header" />
			</div>
			<button v-else :disabled="disabled" @click="triggerAction" class="draggable" :class="myClass" :style="getPosition" v-ripple>
				<v-progress-circular
				v-if="loading"
				:size="30"
				:width="3"
				color="blue-lighten-3"
				indeterminate
				></v-progress-circular>
				<v-icon v-else>{{icon}}</v-icon>
			</button>
		</v-lazy>
</template>

<script>
import interact from 'interactjs';
import { useMain } from '@/stores/Main.js';

export default {
    props: {
      allow_slot: Boolean,  
      name: String,  
      isEnabled: Boolean,  
      isRestricted: Boolean,  
      icon: String,  
      class: String,  
      loading: Boolean,  
      disabled: Boolean,  
      action: Function,
      params: Object
    },
    data: function () {
        return {
			item: {
				x: 50,
				y: 50
			},
        }
    },
	mounted() {
		this.initDraggable();
	},
	computed: {
		/**
		 * Return the inline css styles for the item1, based on its position
		 *
		 * @return {String}
		 */
		getPosition() {
			return `transform: translate(${this.item.x}px, ${this.item.y}px)`;
		},
		main: function () {
			return useMain();
		},
		myClass: function () {
			return `${this.class} ${this.name}`;
		}
	},
	methods: {
		/**
		 * Turn on the interactjs library events
		 */
		initDraggable() {
			let restriction = null;
			if (this.isRestricted) {
				restriction = {
					restriction: ".dropzone",
					endOnly: false,
					elementRect: { top: 0, left: 0, bottom: 1, right: 1 }
				};
			}

			interact(`.${this.name}`).draggable({
				restrict: restriction,
				inertia: true,
				modifiers: [
				interact.modifiers.restrictRect({
					restriction: '.swipe-modal-content',
					endOnly: true
				})],
				onmove: (e) => {
					this.item.x += e.dx;
					this.item.y += e.dy;
				}
			});
		},

		/**
		 * Toggle the draggable status of the element
		 */
		toggleDraggable() {
			if (this.isEnabled) {
				interact(`.${this.name}`).unset();
			} else {
				this.initDraggable();
			}
			this.isEnabled = !this.isEnabled;
		},

		/**
		 * Toggle the restriction status of the zone
		 */
		toggleRestriction() {
			this.isRestricted = !this.isRestricted;
			interact(`.${this.name}`).unset();
			this.initDraggable();
		},
		triggerAction: function () {
			this.action();
		}
	}
}
</script>

<style scoped>

.draggable {
    z-index: 99999999 !important;
	touch-action: none;
	user-select: none;
    width: 50px; /* Set the width to your desired size */
    height: 50px; /* Set the height to the same value as the width */
    background-color: transparent;
    color: white;
    border: none;
    border-radius: 50%; /* This makes the button circular */
    cursor: pointer;
    font-size: 16px;
    text-align: center;
    line-height: 50px; /* Aligns the text vertically in the center */
    display: inline-block;
}

</style>
