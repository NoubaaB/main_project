import axios from 'axios';
import moment from 'moment';
import _ from 'lodash';
window.axios = axios;
window.moment = moment;
window._ = _;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
