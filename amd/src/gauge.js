import Templates from 'core/templates';
import Widget from 'lytix_helper/widget';
import {makeLoggingFunction} from 'lytix_logs/logs';

const
    GAP_FACTOR = 0.333,
    GAP_SIZE = Math.PI * 2 * GAP_FACTOR, // Radians
    GAP_OFFSET = GAP_SIZE / 2,
    MAX_ARC = Math.PI * 2 - GAP_SIZE,
    STROKE_WIDTH = 5,
    NEEDLE_LENGTH = 28;

const
    viewBox = {
        min: -50,
        length: 100,
        height: 95,
    },
    view = {
        viewBox: viewBox,
        arcs: new Array(3),
        strokeWidth: STROKE_WIDTH * 1.01, // Increase slightly to avoid slight gaps
        captions: new Array(3),
        labels: [
            /* {
                x: 0,
                y: 0,
                number: 0,
                align: 'left',
            } */
        ],
    };

let radius = (viewBox.length - STROKE_WIDTH) / 2; // Reduced by stroke width on each iteration

/**
 * Converts an angle to the cartesian coordinates (x, y) on a given circle.
 * The center is (0, 0).
 * With help from: https://stackoverflow.com/a/18473154
 *
 * @param {Number} radius The radius of the circle.
 * @param {Number} angle The angle in radians.
 * @return {Object} An object with x and y properties.
 */
const polarToCartesian = (radius, angle) => {
    return {
        x: radius * Math.cos(angle),
        y: radius * Math.sin(angle),
    };
};

/**
 * Calculates the necessary data to draw one segment of an SVG arc using the globally set ‘radius’.
 *
 * @param {Number} startAngle The segment starts here.
 * @param {Number} endAngle The segment ends here.
 * @return {Object} Contains data for the inner and outer Arcs to be drawn as <path>.
 */
const calculateSvgArc = (startAngle, endAngle) => {
    return {
         radius: radius,
         start: polarToCartesian(radius, startAngle),
         end: polarToCartesian(radius, endAngle),
    };
};

/**
 * Get data needed for partially drawing the outline of an arc.
 *
 * @param {Number} radius The radius of the arc.
 * @param {Number} fraction The score to be rendered as fraction (50 % → 0.5).
 * @return {Object} Contains the values for ‘stroke-dasharray’ and ‘stroke-dashoffset’.
 */
const calculateDashData = (radius, fraction) => {
    const fullLength = radius * 2 * Math.PI * (1 - GAP_FACTOR);
    return {
        fullLength: fullLength,
        length: fullLength * (1 - fraction),
    };
};


export const init = (contextid, courseid, userid, isteacher) => {
    const log = makeLoggingFunction(userid, courseid, contextid, 'measure');

    const stringsPromise = Widget.getStrings({
        lytix_measure: { // eslint-disable-line camelcase
            identical: [
                'hello',
                'personal_dashboard',
                'unlocked_activities',
                'total_students',
                'number_previous_activities',
                'number_open_activities',
                'mine',
                'lowest',
                'highest',
                'avg',
                'total',
                'summary',
            ],
        },
    });
    const dataPromise = Widget.getData(
        'local_lytix_lytix_measure_measure_get',
        {contextid: contextid, courseid: courseid, userid: userid}
    )
    .then(data => {
        const
            scores = data.Scores,
            // Is being reused for strings
            keys = scores.keys = [
                'Highest',
                'Avg',
                isteacher ? 'Lowest' : 'Mine'
            ];

        const fractions = scores.fractions = new Array(scores.Activity.length);
        scores.currentFractions = null;

        // To be called when a category in the filter is checked
        scores.updateActivityFractions = index => {
            if (!fractions[index]) {
                fractions[index] = new Array(3);
                const max = scores.Max[index];
                for (let i = 0; i < 3; ++i) {
                    fractions[index][i] = scores[keys[i]][index] / max;
                }
            }
            scores.currentFractions = fractions[index];
        };

        // Set the current fractions to ‘total’
        scores.updateActivityFractions(0);

        return data;
    });

    Promise.all([stringsPromise, dataPromise])
    .then(values => {
        const
            strings = view.strings = values[0],
            data = values[1],
            scores = data.Scores,
            currentFractions = scores.currentFractions;

        view.isteacher = isteacher;

        if (isteacher) {
            view.StudentCount = data.StudentCount;
            view.ActivityCount = data.ActivityCount;
        } else {
            view.name = data.Name;
        }

        view.total = strings.total;

        // Draw arcs
        const START_ANGLE = Math.PI / 2 + GAP_OFFSET;
        for (let i = 0; i < 3; ++i) {
            const
                score = currentFractions[i],
                endAngle = START_ANGLE + MAX_ARC;
            const arcData = view.arcs[i] = calculateSvgArc(START_ANGLE, endAngle);
            arcData.dash = calculateDashData(radius, score);
            radius -= STROKE_WIDTH;
        }

        // Caption (percentages as text)
        const startCoordinates = polarToCartesian(radius, START_ANGLE);
        for (let i = 2; i >= 0; --i) {
            view.captions[2 - i] = {
                text: strings[scores.keys[i].toLowerCase()],
                value: Math.round(currentFractions[i] * 100),
                yShift: 0.5 + 2 - i,
                xShift: -(2 - i),
                x: startCoordinates.x,
                y: startCoordinates.y,
            };
        }

        // Draw the placeholder
        radius = (viewBox.length - STROKE_WIDTH) / 2 - STROKE_WIDTH;
        view.base = calculateSvgArc(START_ANGLE, START_ANGLE + MAX_ARC);
        view.base.strokeWidth = STROKE_WIDTH * 3;

        // TODO: draw inner labels (ticks and percentages)

        const
            fullDegree = 360 * (1 - GAP_FACTOR),
            needleIndex = isteacher ? 1 : 2;

        // Draw needle
        {
            const point = polarToCartesian(NEEDLE_LENGTH, START_ANGLE);
            view.needle = {
                r: 5,
                x: point.x,
                y: point.y,
                angle: fullDegree * currentFractions[needleIndex],
            };
        }

        // Filter
        {
            // Here we have to skip the first entry as it represents the total.
            const
                activities = scores.Activity,
                count = activities.length - 1;
            view.filter = new Array(count);
            for (let i = 0; i < count; ++i) {
                view.filter[i] = {
                    label: activities[i + 1], // XXX these are category names, no strings available
                    activityIndex: i + 1,
                };
            }
        }

        return Templates.render('lytix_measure/gauge', view)
        .then(html => {
            const container = document.getElementById('gauge');
            container.innerHTML = html;

            const gauge = container.querySelector('svg');
            new ResizeObserver(() => {
                // TODO: reduce calls with setTimeout()
                const rect = gauge.getBoundingClientRect();
                // Size adaptation would be better if interpolated, but this suffices.
                let factor = 1;
                if (rect.width < 270) {
                    factor = 0.6;
                } else if (rect.width < 280) {
                    factor = 0.7;
                } else if (rect.width < 370) {
                    factor = 0.9;
                }
                // Cannot use ‘entries’ because they do not provide the right dimensions.
                gauge.style.fontSize = viewBox.length / rect.height * factor + 'rem';
            }).observe(container);

            const
                needle = container.querySelector('.needle line'),
                arcElements = container.querySelectorAll('.arcs path'),
                captions = container.querySelectorAll('.caption .value');
            document.getElementById('gauge-filter').addEventListener('change', (event) => {
                const activityIndex = event.target.dataset.activityindex;

                scores.updateActivityFractions(activityIndex);
                log('FILTER', 'ON', scores.Activity[activityIndex]);

                for (let i = 0; i < 3; ++i) {
                    const
                        arcElement = arcElements[i],
                        arcData = view.arcs[i],
                        fraction = scores.currentFractions[i],
                        length = arcData.dash.fullLength * (1 - fraction);
                    arcElement.setAttribute('stroke-dashoffset', length);
                    captions[2 - i].innerHTML = Math.round(fraction * 100);
                }
                const needleAngle = fullDegree * scores.currentFractions[needleIndex];
                needle.setAttribute('transform', 'rotate(' + needleAngle + ')');
            });

            return;
        });
    })
    .catch(err => window.console.debug(err));
};
