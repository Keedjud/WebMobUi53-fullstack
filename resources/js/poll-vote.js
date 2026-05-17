import './bootstrap';
import { createApp } from 'vue';
import PollVoteApp from './AppPollVote.vue';

const el = document.getElementById('app');
const props = JSON.parse(el.dataset.props ?? '{}');

createApp(PollVoteApp, props).mount(el);
