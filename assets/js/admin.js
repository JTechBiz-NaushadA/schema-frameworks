(function () {
    'use strict';

    function setConfigState(toggle) {
        var targetId = toggle.getAttribute('data-config-target');

        if (!targetId) {
            return;
        }

        var target = document.getElementById(targetId);

        if (!target) {
            return;
        }

        target.style.display = toggle.checked ? '' : 'none';

        var card = toggle.closest('.schema-frameworks-module-card');
        if (card) {
            card.classList.toggle('is-selected', toggle.checked);
        }

        target.querySelectorAll('input, textarea, select').forEach(function (field) {
            field.disabled = !toggle.checked;
        });
    }

    function bindSchemaToggles(scope) {
        scope.querySelectorAll('.schema-frameworks-schema-toggle').forEach(function (toggle) {
            if (toggle.dataset.schemaToggleBound === '1') {
                setConfigState(toggle);
                return;
            }

            toggle.addEventListener('change', function () {
                setConfigState(toggle);
            });

            toggle.dataset.schemaToggleBound = '1';
            setConfigState(toggle);
        });
    }

    function initPageMappings() {
        var container = document.getElementById('schema-frameworks-page-mappings');
        var addButton = document.getElementById('schema-frameworks-add-page');
        var template = document.getElementById('schema-frameworks-page-template');

        if (!container || !addButton || !template) {
            return;
        }

        var index = container.querySelectorAll('.schema-frameworks-page-mapping').length;

        function bindRemoveButtons(scope) {
            scope.querySelectorAll('.schema-frameworks-remove-page').forEach(function (button) {
                if (button.dataset.removeBound === '1') {
                    return;
                }

                button.addEventListener('click', function () {
                    var mapping = button.closest('.schema-frameworks-page-mapping');
                    if (mapping) {
                        mapping.remove();
                    }
                });

                button.dataset.removeBound = '1';
            });
        }

        bindRemoveButtons(container);
        bindSchemaToggles(container);

        addButton.addEventListener('click', function () {
            var html = template.innerHTML
                .replace(/__PAGE_NAME__/g, 'schema_frameworks_config[pages][' + index + '][page_id]')
                .replace(/__PAGE_INDEX__/g, String(index))
                .replace(/__SCHEMA_PREFIX__/g, 'schema_frameworks_config[pages][' + index + ']');

            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            var mapping = wrapper.firstElementChild;

            if (!mapping) {
                return;
            }

            container.appendChild(mapping);
            bindRemoveButtons(mapping);
            bindSchemaToggles(mapping);
            index++;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPageMappings);
    } else {
        initPageMappings();
    }
})();


(function () {
    'use strict';

    function initSameAs() {
        var container = document.getElementById('schema-frameworks-same-as');
        var addButton = document.getElementById('schema-frameworks-add-same-as');
        var template = document.getElementById('schema-frameworks-same-as-template');

        if (!container || !addButton || !template) {
            return;
        }

        var index = container.querySelectorAll('.schema-frameworks-same-as-row').length;

        function bindRemoveButtons(scope) {
            scope.querySelectorAll('.schema-frameworks-remove-same-as').forEach(function (button) {
                if (button.dataset.removeBound === '1') {
                    return;
                }

                button.addEventListener('click', function () {
                    var row = button.closest('.schema-frameworks-same-as-row');
                    if (row) {
                        row.remove();
                    }
                });

                button.dataset.removeBound = '1';
            });
        }

        bindRemoveButtons(container);

        addButton.addEventListener('click', function () {
            var html = template.innerHTML.replace(/__SAME_AS_INDEX__/g, String(index));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            var row = wrapper.firstElementChild;

            if (!row) {
                return;
            }

            container.appendChild(row);
            bindRemoveButtons(row);
            index++;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSameAs);
    } else {
        initSameAs();
    }
})();


(function () {
    'use strict';

    function getEditorContainer() {
        return document.querySelector('.schema-frameworks-page-editor');
    }

    function getSchemaToggle(container, schemaId) {
        return container.querySelector('.schema-frameworks-editor-module-card input[type="checkbox"][value="' + schemaId + '"]');
    }

    function getSchemaTab(container, schemaId) {
        return container.querySelector('.schema-frameworks-editor-tab[data-schema-tab="' + schemaId + '"]');
    }

    function getSchemaPanel(container, schemaId) {
        return document.getElementById('schema-frameworks-editor-config-' + schemaId);
    }

    function setActiveSchemaTab(container, schemaId) {
        var tabs = container.querySelectorAll('.schema-frameworks-editor-tab');
        var panels = container.querySelectorAll('.schema-frameworks-editor-config-panel');
        var activeFound = false;

        tabs.forEach(function (tab) {
            var isActive = tab.getAttribute('data-schema-tab') === schemaId && tab.style.display !== 'none';
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            if (isActive) {
                activeFound = true;
            }
        });

        panels.forEach(function (panel) {
            var isActive = activeFound && panel.id === 'schema-frameworks-editor-config-' + schemaId;
            panel.style.display = isActive ? '' : 'none';
            panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        var emptyState = container.querySelector('.schema-frameworks-editor-no-selection');
        if (emptyState) {
            emptyState.style.display = activeFound ? 'none' : '';
        }
    }

    function getFirstSelectedSchema(container) {
        var toggles = container.querySelectorAll('.schema-frameworks-editor-module-card input[type="checkbox"]');
        for (var i = 0; i < toggles.length; i++) {
            if (toggles[i].checked && getSchemaTab(container, toggles[i].value)) {
                return toggles[i].value;
            }
        }

        return '';
    }

    function syncSchemaTabs(container, preferredSchemaId) {
        var toggles = container.querySelectorAll('.schema-frameworks-editor-module-card input[type="checkbox"]');
        var activeSchemaId = preferredSchemaId || '';

        toggles.forEach(function (toggle) {
            var schemaId = toggle.value;
            var tab = getSchemaTab(container, schemaId);
            var panel = getSchemaPanel(container, schemaId);

            if (!tab) {
                if (panel) {
                    panel.style.display = 'none';
                    panel.setAttribute('aria-hidden', 'true');
                }
                return;
            }

            tab.style.display = toggle.checked ? '' : 'none';

            if (panel) {
                panel.querySelectorAll('input, textarea, select').forEach(function (field) {
                    field.disabled = !toggle.checked;
                });
            }

            if (!toggle.checked && schemaId === activeSchemaId) {
                activeSchemaId = '';
            }
        });

        if (!activeSchemaId || !getSchemaTab(container, activeSchemaId) || getSchemaTab(container, activeSchemaId).style.display === 'none') {
            activeSchemaId = getFirstSelectedSchema(container);
        }

        setActiveSchemaTab(container, activeSchemaId);
    }

    function bindSchemaTabEvents(container) {
        container.querySelectorAll('.schema-frameworks-editor-tab').forEach(function (tab) {
            if (tab.dataset.schemaTabBound === '1') {
                return;
            }

            tab.addEventListener('click', function () {
                var schemaId = tab.getAttribute('data-schema-tab');
                var toggle = getSchemaToggle(container, schemaId);

                if (!toggle || !toggle.checked) {
                    return;
                }

                setActiveSchemaTab(container, schemaId);
            });

            tab.dataset.schemaTabBound = '1';
        });
    }

    function bindSchemaEditorToggles(container) {
        container.querySelectorAll('.schema-frameworks-editor-module-card input[type="checkbox"]').forEach(function (toggle) {
            if (toggle.dataset.schemaEditorBound === '1') {
                return;
            }

            toggle.addEventListener('change', function () {
                syncSchemaTabs(container, toggle.checked ? toggle.value : '');
            });

            toggle.dataset.schemaEditorBound = '1';
        });
    }

    function syncRelationshipRow(row) {
        if (!row) {
            return;
        }

        var source = row.querySelector('select[name*="[source]"]');
        var property = row.querySelector('select[name*="[property]"]');
        var target = row.querySelector('select[name*="[target]"]');
        if (!source || !property || !target) {
            return;
        }

        if (!property._sfRelationshipOptions) {
            property._sfRelationshipOptions = Array.prototype.slice.call(
                property.querySelectorAll('option[data-source]')
            ).map(function (option) {
                return option.cloneNode(true);
            });
        }
        if (!target._sfRelationshipOptions) {
            target._sfRelationshipOptions = Array.prototype.slice.call(
                target.querySelectorAll('option[data-target-id]')
            ).map(function (option) {
                return option.cloneNode(true);
            });
        }

        var sourceValue = source.value;
        var previousProperty = property.value;
        var previousTarget = target.value;
        var propertyPlaceholder = property.options.length ? property.options[0].cloneNode(true) : null;
        var targetPlaceholder = target.options.length ? target.options[0].cloneNode(true) : null;

        property.options.length = 0;
        if (propertyPlaceholder) {
            propertyPlaceholder.hidden = false;
            property.appendChild(propertyPlaceholder);
        }

        var matchingProperties = property._sfRelationshipOptions.filter(function (option) {
            return option.getAttribute('data-source') === sourceValue;
        });
        matchingProperties.forEach(function (option) {
            var clone = option.cloneNode(true);
            clone.hidden = false;
            clone.selected = false;
            property.appendChild(clone);
        });

        var propertyIsValid = matchingProperties.some(function (option) {
            return option.value === previousProperty;
        });
        property.value = propertyIsValid ? previousProperty : '';

        var selectedPropertyOption = property.selectedOptions.length
            ? property.selectedOptions[0]
            : null;
        var allowedTargets = selectedPropertyOption
            ? (selectedPropertyOption.getAttribute('data-targets') || '').split(',').filter(Boolean)
            : [];

        target.options.length = 0;
        if (targetPlaceholder) {
            targetPlaceholder.hidden = false;
            target.appendChild(targetPlaceholder);
        }

        var matchingTargets = target._sfRelationshipOptions.filter(function (option) {
            return allowedTargets.indexOf(option.getAttribute('data-target-id') || '') !== -1;
        });
        matchingTargets.forEach(function (option) {
            var clone = option.cloneNode(true);
            clone.hidden = false;
            clone.selected = false;
            target.appendChild(clone);
        });

        var targetIsValid = matchingTargets.some(function (option) {
            return option.value === previousTarget;
        });
        target.value = targetIsValid ? previousTarget : '';
    }

    function bindRelationshipRows(scope) {
        if (!scope) {
            return;
        }

        var rows = [];
        if (scope.matches && scope.matches('.schema-frameworks-relationship-row')) {
            rows.push(scope);
        }
        scope.querySelectorAll('.schema-frameworks-relationship-row').forEach(function (row) {
            rows.push(row);
        });

        rows.forEach(function (row) {
            if (row.dataset.relationshipBound === '1') {
                syncRelationshipRow(row);
                return;
            }
            var source = row.querySelector('select[name*="[source]"]');
            var property = row.querySelector('select[name*="[property]"]');
            if (source) {
                source.addEventListener('change', function () {
                    syncRelationshipRow(row);
                });
            }
            if (property) {
                property.addEventListener('change', function () {
                    syncRelationshipRow(row);
                });
            }
            syncRelationshipRow(row);
            row.dataset.relationshipBound = '1';
        });
    }

    function bindRepeaterRemove(scope) {
        scope.querySelectorAll('.schema-frameworks-repeater-remove').forEach(function (button) {
            if (button.dataset.repeaterRemoveBound === '1') {
                return;
            }

            button.addEventListener('click', function () {
                var row = button.closest('.schema-frameworks-repeater-row');
                if (row) {
                    row.remove();
                }
            });

            button.dataset.repeaterRemoveBound = '1';
        });
    }

    function initPageEditorSchemas() {
        var container = getEditorContainer();
        if (!container) {
            return;
        }

        bindSchemaTabEvents(container);
        bindSchemaEditorToggles(container);
        syncSchemaTabs(container, '');

        container.querySelectorAll('.schema-frameworks-repeater').forEach(function (repeater) {
            if (repeater.dataset.repeaterBound === '1') {
                return;
            }

            var addButton = repeater.querySelector('.schema-frameworks-repeater-add');
            var template = repeater.querySelector('.schema-frameworks-repeater-template');
            if (!addButton || !template) {
                return;
            }

            addButton.addEventListener('click', function () {
                var index = repeater.querySelectorAll('.schema-frameworks-repeater-row').length;
                var html = template.innerHTML.replace(/__ROW_INDEX__/g, String(index));
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                var row = wrapper.firstElementChild;

                if (!row) {
                    return;
                }

                repeater.insertBefore(row, addButton);
                bindRepeaterRemove(row);
                bindRelationshipRows(row);
            });

            repeater.querySelectorAll('.schema-frameworks-repeater-row').forEach(function (row) {
                bindRepeaterRemove(row);
            });

            if (repeater.classList.contains('schema-frameworks-relationship-repeater')) {
                bindRelationshipRows(repeater);
            }

            repeater.dataset.repeaterBound = '1';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPageEditorSchemas);
    } else {
        initPageEditorSchemas();
    }
})();

(function () {
	'use strict';
	var root = document.getElementById('sf-batch-scan');
	if (!root || typeof window.fetch !== 'function' || typeof SchemaFrameworksBatchScan === 'undefined') return;
	var startButton = document.getElementById('sf-scan-start');
	var resumeButton = document.getElementById('sf-scan-resume');
	var pauseButton = document.getElementById('sf-scan-pause');
	var batchSize = document.getElementById('sf-scan-batch-size');
	var status = document.getElementById('sf-scan-status');
	var progress = document.getElementById('sf-scan-progress');
	var processedCount = document.getElementById('sf-scan-processed');
	var remainingCount = document.getElementById('sf-scan-remaining');
	var failedCount = document.getElementById('sf-scan-failed');
	var updatedAt = document.getElementById('sf-scan-updated');
	var running = false;
	var paused = false;
	var animationFrame = null;
	var strings = SchemaFrameworksBatchScan.strings || {};

	function request(action, values) {
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', SchemaFrameworksBatchScan.nonce);
		Object.keys(values || {}).forEach(function (key) { body.set(key, values[key]); });
		return fetch(SchemaFrameworksBatchScan.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
			.then(function (response) { return response.json(); })
			.then(function (result) { if (!result || !result.success) throw new Error(result && result.data && result.data.message ? result.data.message : (strings.error || 'Request failed')); return result.data; });
	}
	function animateProgress(target) {
		if (!progress) return;
		if (animationFrame !== null) window.cancelAnimationFrame(animationFrame);
		var from = Number(progress.value || 0);
		target = Math.max(0, Math.min(100, Number(target) || 0));
		var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (reduceMotion || from === target) { progress.value = target; return; }
		var started = null;
		var duration = 480;
		function frame(now) {
			if (started === null) started = now;
			var t = Math.min(1, (now - started) / duration);
			/* Ease-out cubic: responsive but visibly smooth after each batch. */
			var eased = 1 - Math.pow(1 - t, 3);
			progress.value = from + ((target - from) * eased);
			if (t < 1) animationFrame = window.requestAnimationFrame(frame);
			else { progress.value = target; animationFrame = null; }
		}
		animationFrame = window.requestAnimationFrame(frame);
	}
	function render(state) {
		if (!state) return;
		var pct = state.total > 0 ? Math.round((state.processed / state.total) * 100) : 0;
		var remaining = Math.max(0, Number(state.total || 0) - Number(state.processed || 0));
		root.setAttribute('data-running', state.status || 'idle');
		progress.max = 100;
		animateProgress(pct);
		progress.setAttribute('aria-valuetext', pct + '% complete');
		if (processedCount) processedCount.textContent = String(state.processed || 0) + ' / ' + String(state.total || 0);
		if (remainingCount) remainingCount.textContent = String(remaining);
		if (failedCount) failedCount.textContent = String(state.failed || 0);
		if (updatedAt) updatedAt.textContent = state.updated_at || '—';
		status.className = 'sf-scan-status is-' + String(state.status || 'idle').replace(/[^a-z0-9_-]/g, '');
		status.textContent = 'Status: ' + state.status + ' — ' + (state.processed || 0) + ' / ' + (state.total || 0) + ' attempted (' + pct + '%). Failed URL loads: ' + (state.failed || 0) + '. Batch size: ' + (state.batch_size || '—') + '.';
		if (state.status === 'completed') status.textContent += ' ' + (strings.completed || 'Scan completed.');
		if (state.status === 'paused') status.textContent += ' ' + (strings.paused || 'Scan paused.');
		if (startButton) startButton.disabled = !!running;
		if (resumeButton) resumeButton.disabled = state.status === 'running' || state.status === 'completed';
		if (pauseButton) pauseButton.disabled = state.status !== 'running';
		if (batchSize) batchSize.disabled = state.status === 'running';
	}
	function runLoop() {
		if (!running || paused) return;
		request('schema_frameworks_scan_next', {}).then(function (data) {
			render(data.state);
			if (data.state.status !== 'running') { running = false; render(data.state); return; }
			if (!data.items || !data.items.length) { running = false; render(data.state); return; }
			var failed = 0;
			var attempted = 0;
			return data.items.reduce(function (chain, item) {
				return chain.then(function () {
					if (!running || paused) return;
					attempted++;
					if (!item.url) { failed++; return; }
					return fetch(item.url, { method: 'GET', credentials: 'same-origin', redirect: 'error', cache: 'no-store' })
						.then(function (response) { if (!response.ok) failed++; return response.text(); })
						.catch(function () { failed++; });
				});
			}, Promise.resolve()).then(function () {
				if (!attempted) return;
				return request('schema_frameworks_scan_progress', { attempted: attempted, failed: failed }).then(function (state) {
					render(state);
					if (state.status === 'running' && running && !paused) return runLoop();
					running = false;
				});
			});
		}).catch(function (error) { running = false; status.className = 'sf-scan-status is-error'; status.textContent = (strings.error || 'Scan error.') + ' ' + error.message; if (startButton) startButton.disabled = false; if (resumeButton) resumeButton.disabled = false; if (pauseButton) pauseButton.disabled = true; });
	}
	startButton.addEventListener('click', function () {
		if (running) return;
		if (['running', 'paused'].indexOf(root.getAttribute('data-running')) !== -1 && !window.confirm('Starting a new scan replaces the saved scan progress. Continue?')) return;
		startButton.disabled = true;
		request('schema_frameworks_scan_start', { batch_size: batchSize.value }).then(function (state) {
			root.setAttribute('data-running', state.status); paused = false; running = state.status === 'running'; render(state); if (running) runLoop();
		}).catch(function (error) { status.textContent = error.message; }).then(function () { startButton.disabled = false; });
	});
	resumeButton.addEventListener('click', function () {
		if (running) return;
		request('schema_frameworks_scan_control', { command: 'resume' }).then(function (state) { root.setAttribute('data-running', state.status); render(state); paused = false; running = state.status === 'running'; if (running) runLoop(); }).catch(function (error) { status.textContent = error.message; });
	});
	pauseButton.addEventListener('click', function () {
		paused = true;
		request('schema_frameworks_scan_control', { command: 'pause' }).then(function (state) { root.setAttribute('data-running', state.status); render(state); running = false; }).catch(function (error) { status.textContent = error.message; });
	});
})();

(function () {
	'use strict';
	var dashboard = document.querySelector('.schema-frameworks-dashboard');
	if (!dashboard) return;
	var tabs = Array.prototype.slice.call(dashboard.querySelectorAll('.sf-dashboard-tab[role="tab"]'));
	if (!tabs.length) return;
	function activate(tab, moveFocus) {
		var targetId = tab.getAttribute('data-panel');
		tabs.forEach(function (item) {
			var active = item === tab;
			item.classList.toggle('is-active', active);
			item.setAttribute('aria-selected', active ? 'true' : 'false');
			item.setAttribute('tabindex', active ? '0' : '-1');
			var panel = document.getElementById(item.getAttribute('aria-controls'));
			if (panel) panel.hidden = !active;
		});
		if (moveFocus) tab.focus();
	}
	tabs.forEach(function (tab, index) {
		tab.addEventListener('click', function () { activate(tab, false); });
		tab.addEventListener('keydown', function (event) {
			var nextIndex = index;
			if (event.key === 'ArrowRight' || event.key === 'ArrowDown') nextIndex = (index + 1) % tabs.length;
			else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') nextIndex = (index - 1 + tabs.length) % tabs.length;
			else if (event.key === 'Home') nextIndex = 0;
			else if (event.key === 'End') nextIndex = tabs.length - 1;
			else return;
			event.preventDefault();
			activate(tabs[nextIndex], true);
		});
	});
	var requestedTab = (window.location.search.match(/[?&]sf_dashboard_tab=([^&]+)/) || [])[1];
	if (requestedTab) { try { requestedTab = decodeURIComponent(requestedTab.replace(/\+/g, ' ')); } catch (error) { requestedTab = ''; } }
	var selected = tabs.find(function (tab) { return tab.id === ('sf-tab-' + requestedTab); }) || tabs.find(function (tab) { return tab.getAttribute('aria-selected') === 'true'; }) || tabs[0];
	activate(selected, false);
})();

(function () {
	'use strict';
	var dashboard = document.querySelector('.schema-frameworks-dashboard');
	if (!dashboard) return;
	var forms = dashboard.querySelectorAll('form[action*="admin-post.php"]');
	Array.prototype.forEach.call(forms, function (form) {
		var action = form.querySelector('input[name="action"]');
		if (!action || action.value !== 'schema_frameworks_refresh_diagnostics') return;
		form.addEventListener('submit', function (event) {
			if (!window.confirm('Remove diagnostic snapshots older than 30 days and refresh the dashboard? Recent snapshots will be kept.')) event.preventDefault();
		});
	});
})();

(function () {
    'use strict';
    function initPostFaqRepeater() {
        var container = document.getElementById('schema-frameworks-post-faq-items');
        var addButton = document.getElementById('schema-frameworks-add-post-faq');
        var template = document.getElementById('schema-frameworks-post-faq-template');
        if (!container || !addButton || !template) { return; }
        var index = container.querySelectorAll('.schema-frameworks-post-faq-row').length;
        container.addEventListener('click', function (event) {
            var removeButton = event.target.closest('.schema-frameworks-remove-post-faq');
            if (removeButton) {
                event.preventDefault();
                var row = removeButton.closest('.schema-frameworks-post-faq-row');
                if (row) { row.remove(); }
            }
        });
        addButton.addEventListener('click', function () {
            if (container.querySelectorAll('.schema-frameworks-post-faq-row').length >= 20) {
                window.alert('A maximum of 20 FAQ items can be added to one post.');
                return;
            }
            var html = template.innerHTML.replace(/__FAQ_INDEX__/g, String(index++));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            if (wrapper.firstElementChild) { container.appendChild(wrapper.firstElementChild); }
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initPostFaqRepeater); }
    else { initPostFaqRepeater(); }
})();