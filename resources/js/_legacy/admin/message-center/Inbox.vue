<template>
    <v-defaults-provider :defaults="fieldDefaults">
        <div class="tt-dash">
            <!-- ═════════ Compact hero (same family as the Dashboard hero) ═════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="d-flex flex-wrap align-center ga-3">
                            <h1 class="tt-h1">Inbox</h1>
                            <span class="tt-pill"><v-icon size="13">mdi-forum-outline</v-icon>Message center</span>
                            <span class="tt-pill tt-pill-live"><span class="tt-pulse"></span>{{ connectedCount }} of {{ totalChannels }} channels live</span>
                        </div>
                        <div class="tt-hero-sub">Every customer message from every platform, in one place</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <div class="tt-seg" role="tablist" aria-label="Inbox scope">
                            <button v-for="s in scopes" :key="s.value" type="button" role="tab" :aria-selected="scope === s.value" :class="{ on: scope === s.value }" @click="setScope(s.value)">{{ s.label }}</button>
                        </div>
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-email-open-multiple-outline" @click="markAllRead">Mark all read</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plug" @click="channelDialog = true">Manage channels</v-btn>
                    </div>
                </div>

                <div class="tt-hero-strip">
                    <div v-for="h in heroStats" :key="h.label" class="tt-hero-item" :style="{ '--hue': h.color }" :title="h.note">
                        <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                        <div class="tt-hero-txt">
                            <div class="tt-hero-value">{{ h.value }}</div>
                            <div class="tt-hero-label">{{ h.label }}</div>
                        </div>
                        <span class="tt-live">{{ h.tag }}</span>
                        <div v-if="h.progress != null" class="tt-hero-progress"><span :style="{ width: h.progress + '%' }"></span></div>
                    </div>
                </div>
            </header>

            <!-- ═════════ Inbox shell ═════════ -->
            <v-card flat class="tt-card tt-shell mt-3">
                <div class="tt-split">
                    <!-- ───── Channel rail: one slim column instead of a wide tab bar ───── -->
                    <nav class="tt-rail" aria-label="Channels">
                        <button class="tt-rail-btn tt-rail-btn--all" :class="{ on: platformFilter === 'all' }" style="--c: #0f9d6b" aria-label="All inboxes" :aria-pressed="platformFilter === 'all'" @click="platformFilter = 'all'">
                            <v-icon size="21" color="white">mdi-inbox-multiple</v-icon>
                            <em v-if="totalUnread" class="tt-rail-badge">{{ totalUnread }}</em>
                            <v-tooltip activator="parent" location="end">All inboxes · {{ conversations.length }} chats</v-tooltip>
                        </button>
                        <span class="tt-rail-sep"></span>
                        <button
                            v-for="(p, key) in platforms"
                            :key="key"
                            class="tt-rail-btn"
                            :class="{ on: platformFilter === key, off: !channels[key].connected }"
                            :style="{ '--c': p.color }"
                            :aria-label="p.label"
                            :aria-pressed="platformFilter === key"
                            @click="chanClick(key)"
                        >
                            <v-icon size="21" color="white">{{ p.icon }}</v-icon>
                            <em v-if="unreadByPlatform[key]" class="tt-rail-badge">{{ unreadByPlatform[key] }}</em>
                            <v-tooltip activator="parent" location="end">{{ p.label }} · {{ channels[key].connected ? (countByPlatform[key] || 0) + " chats" : "Not connected" }}</v-tooltip>
                        </button>
                        <v-spacer />
                        <button class="tt-rail-btn tt-rail-btn--ghost" aria-label="Manage channels" @click="channelDialog = true">
                            <v-icon size="20" color="white">mdi-plus</v-icon>
                            <v-tooltip activator="parent" location="end">Connect a channel</v-tooltip>
                        </button>
                    </nav>

                    <div class="tt-grid" :class="{ 'show-thread': showThread, 'has-info': showInfo && active }">
                        <!-- ───── Conversation list ───── -->
                        <section class="tt-pane tt-list-pane">
                            <div class="tt-list-top">
                                <div class="tt-list-title">
                                    <span class="tt-list-ic" :style="{ background: activeChannel.color }"><v-icon size="16" color="white">{{ activeChannel.icon }}</v-icon></span>
                                    <div>
                                        <b>{{ activeChannel.label }}</b>
                                        <small>{{ filteredConversations.length }} conversations</small>
                                    </div>
                                </div>
                                <v-menu content-class="tt-overlay">
                                    <template #activator="{ props }">
                                        <v-btn v-bind="props" icon variant="outlined" size="small" class="tt-icon-btn" aria-label="Filter and sort"><v-icon size="18">mdi-tune-variant</v-icon></v-btn>
                                    </template>
                                    <v-list density="compact" min-width="230">
                                        <v-list-subheader>Show</v-list-subheader>
                                        <v-list-item v-for="s in extraFilters" :key="s.value" :prepend-icon="s.icon" :title="s.title" :active="statusFilter === s.value" @click="statusFilter = s.value" />
                                        <v-divider />
                                        <v-list-subheader>Sort by</v-list-subheader>
                                        <v-list-item v-for="s in sortOptions" :key="s.value" :title="s.title" :active="sortBy === s.value" @click="sortBy = s.value" />
                                        <v-divider />
                                        <v-list-item prepend-icon="mdi-email-open-multiple-outline" title="Mark all as read" @click="markAllRead" />
                                    </v-list>
                                </v-menu>
                            </div>

                            <div class="tt-list-search">
                                <v-text-field v-model="search" hide-details clearable placeholder="Search name, phone or message" prepend-inner-icon="mdi-magnify" class="tt-search" />
                            </div>

                            <div class="tt-tabs" role="tablist" aria-label="Conversation status">
                                <button v-for="t in statusTabs" :key="t.value" role="tab" :aria-selected="statusFilter === t.value" :class="{ on: statusFilter === t.value }" @click="statusFilter = t.value">
                                    {{ t.label }}<span v-if="statusCounts[t.value]">{{ statusCounts[t.value] }}</span>
                                </button>
                            </div>

                            <div class="tt-convo-list">
                                <div
                                    v-for="c in filteredConversations"
                                    :key="c.id"
                                    class="tt-convo"
                                    :class="{ active: c.id === activeId, unread: c.unread > 0 }"
                                    :style="{ '--c': platforms[c.platform].color }"
                                    tabindex="0"
                                    @click="selectConversation(c)"
                                    @keydown.enter="selectConversation(c)"
                                >
                                    <div class="tt-avatar" :style="{ background: avatarTint(c.customer) }">
                                        {{ initials(c.customer) }}
                                        <span class="tt-platform-badge" :style="{ background: platforms[c.platform].color }" :title="platforms[c.platform].label"><v-icon size="10" color="white">{{ platforms[c.platform].icon }}</v-icon></span>
                                    </div>
                                    <div class="tt-convo-main">
                                        <div class="d-flex align-center ga-1">
                                            <v-icon v-if="c.starred" size="13" color="#f59e0b">mdi-star</v-icon>
                                            <span class="tt-convo-name">{{ c.customer }}</span>
                                            <v-spacer />
                                            <span class="tt-convo-time" :class="{ late: isLate(c) }">{{ relTime(lastMsg(c).at) }}</span>
                                        </div>
                                        <div class="d-flex align-center ga-2">
                                            <span class="tt-convo-preview">{{ previewText(c) }}</span>
                                            <span v-if="c.unread" class="tt-unread">{{ c.unread }}</span>
                                        </div>
                                        <div class="tt-convo-meta">
                                            <span v-if="c.status === 'resolved'" class="tt-chip tone-green">Resolved</span>
                                            <span v-else-if="isSnoozed(c)" class="tt-chip tone-violet">Snoozed</span>
                                            <span v-else-if="!c.assignee" class="tt-chip tone-amber">Unassigned</span>
                                            <span v-else class="tt-owner" :title="'Assigned to ' + c.assignee"><i>{{ initials(c.assignee) }}</i>{{ c.assignee.split(" ")[0] }}</span>
                                            <span v-if="isLate(c)" class="tt-chip tone-red">Waiting {{ relTime(lastMsg(c).at) }}</span>
                                            <span v-else-if="c.tags[0]" class="tt-chip tone-gray">{{ c.tags[0] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!filteredConversations.length" class="tt-empty-list">
                                    <div class="tt-empty-ic tt-empty-ic--sm"><v-icon size="26" color="#0f9d6b">mdi-inbox-outline</v-icon></div>
                                    <div class="tt-strong mt-3">Nothing here</div>
                                    <div class="tt-sub">Clear the search or choose another filter to see more chats.</div>
                                </div>
                            </div>
                        </section>

                        <!-- ───── Chat room ───── -->
                        <section class="tt-pane tt-chat-pane">
                            <template v-if="active">
                                <header class="tt-chat-head">
                                    <v-btn icon variant="text" size="small" class="tt-back-btn" aria-label="Back to list" @click="showThread = false"><v-icon>mdi-arrow-left</v-icon></v-btn>
                                    <div class="tt-avatar tt-avatar--md" :style="{ background: avatarTint(active.customer) }">
                                        {{ initials(active.customer) }}
                                        <span class="tt-platform-badge" :style="{ background: platforms[active.platform].color }"><v-icon size="10" color="white">{{ platforms[active.platform].icon }}</v-icon></span>
                                    </div>
                                    <div class="tt-chat-title">
                                        <div class="d-flex align-center ga-2">
                                            <span class="tt-chat-name">{{ active.customer }}</span>
                                            <span class="tt-chip" :class="'tone-' + tierTone">{{ tier }}</span>
                                        </div>
                                        <div class="tt-sub tt-clip">{{ platforms[active.platform].label }} · {{ active.handle }}<template v-if="active.city"> · {{ active.city }}</template></div>
                                    </div>
                                    <v-spacer />
                                    <span v-if="windowInfo.applies" class="tt-window-chip" :class="windowInfo.open ? 'ok' : 'closed'" title="Free-form replies are allowed for 24 hours after the customer's last message">
                                        <v-icon size="13">mdi-timer-outline</v-icon>{{ windowInfo.open ? windowInfo.label : "Window closed" }}
                                    </span>

                                    <v-menu content-class="tt-overlay">
                                        <template #activator="{ props }">
                                            <button v-bind="props" class="tt-assign-btn" :class="{ empty: !active.assignee }" aria-label="Assign conversation">
                                                <span class="tt-assign-av">{{ active.assignee ? initials(active.assignee) : "?" }}</span>
                                                <span class="tt-assign-name">{{ active.assignee ? active.assignee.split(" ")[0] : "Assign" }}</span>
                                                <v-icon size="16">mdi-chevron-down</v-icon>
                                            </button>
                                        </template>
                                        <v-list density="compact" min-width="210">
                                            <v-list-subheader>Assign to</v-list-subheader>
                                            <v-list-item prepend-icon="mdi-account-off-outline" title="Unassigned" :active="!active.assignee" @click="assign('')" />
                                            <v-list-item v-for="s in staff" :key="s" prepend-icon="mdi-account-outline" :title="s" :active="active.assignee === s" @click="assign(s)" />
                                        </v-list>
                                    </v-menu>

                                    <v-btn :color="active.status === 'resolved' ? undefined : '#0f9d6b'" :variant="active.status === 'resolved' ? 'outlined' : 'flat'" size="small" class="text-none tt-resolve-btn" :prepend-icon="active.status === 'resolved' ? 'mdi-reload' : 'mdi-check-circle-outline'" @click="toggleResolved">
                                        {{ active.status === "resolved" ? "Reopen" : "Resolve" }}
                                    </v-btn>
                                    <v-btn icon variant="text" size="small" :aria-label="active.starred ? 'Remove star' : 'Star conversation'" @click="active.starred = !active.starred">
                                        <v-icon size="19" :color="active.starred ? '#f59e0b' : '#5c726e'">{{ active.starred ? "mdi-star" : "mdi-star-outline" }}</v-icon>
                                    </v-btn>
                                    <v-menu content-class="tt-overlay">
                                        <template #activator="{ props }">
                                            <v-btn v-bind="props" icon variant="text" size="small" aria-label="More actions"><v-icon size="19" color="#5c726e">mdi-dots-vertical</v-icon></v-btn>
                                        </template>
                                        <v-list density="compact" min-width="210">
                                            <v-list-subheader>Snooze until</v-list-subheader>
                                            <v-list-item v-for="s in snoozeOptions" :key="s.label" prepend-icon="mdi-alarm-snooze" :title="s.label" @click="snooze(s)" />
                                            <v-divider />
                                            <v-list-item prepend-icon="mdi-email-mark-as-unread" title="Mark as unread" @click="markUnread" />
                                            <v-list-item prepend-icon="mdi-block-helper" title="Mark as spam" base-color="error" @click="markSpam" />
                                        </v-list>
                                    </v-menu>
                                    <v-btn icon variant="text" size="small" aria-label="Customer details" @click="showInfo = !showInfo">
                                        <v-icon size="19" :color="showInfo ? '#0f9d6b' : '#5c726e'">mdi-account-details-outline</v-icon>
                                    </v-btn>
                                </header>

                                <div v-if="active.subject" class="tt-context"><v-icon size="14">mdi-email-outline</v-icon> {{ active.subject }}</div>
                                <div v-if="active.postTitle" class="tt-context tt-context--blue"><v-icon size="14">mdi-post-outline</v-icon> On post: {{ active.postTitle }}</div>

                                <div class="tt-thread-wrap">
                                    <div ref="threadBody" class="tt-thread" @scroll="onScroll">
                                        <template v-for="it in threadItems" :key="it.id">
                                            <div v-if="it.divider" class="tt-day"><span>{{ it.label }}</span></div>
                                            <div v-else-if="it.sys" class="tt-msg-system">{{ it.m.text }} · {{ msgTime(it.m.at) }}</div>
                                            <div v-else class="tt-row" :class="[it.m.from, { first: it.first, last: it.last }]">
                                                <div v-if="it.m.from === 'customer'" class="tt-row-av">
                                                    <div v-if="it.last" class="tt-avatar tt-avatar--sm" :style="{ background: avatarTint(active.customer) }">{{ initials(active.customer) }}</div>
                                                </div>
                                                <div class="tt-row-body">
                                                    <div v-if="it.first && it.m.from !== 'customer'" class="tt-row-name">{{ it.m.from === "note" ? "Internal note" : it.m.agent || "You" }}</div>
                                                    <div class="tt-bubble">
                                                        <div v-if="it.m.from === 'note'" class="tt-bubble-tag"><v-icon size="12">mdi-lock-outline</v-icon> Only your team can see this</div>
                                                        <div v-if="it.m.template" class="tt-bubble-tag"><v-icon size="12">mdi-file-document-outline</v-icon> Template · {{ it.m.template }}</div>
                                                        <div v-if="it.m.public === false" class="tt-bubble-tag"><v-icon size="12">mdi-lock-outline</v-icon> Sent as a private message</div>

                                                        <div v-if="it.m.product" class="tt-prod">
                                                            <div class="tt-prod-img"><v-icon size="24" color="#0f9d6b">mdi-package-variant-closed</v-icon></div>
                                                            <div class="tt-prod-info">
                                                                <b>{{ it.m.product.name }}</b>
                                                                <span>{{ money(it.m.product.price) }}</span>
                                                                <small :class="it.m.product.stock ? 'in' : 'out'">{{ it.m.product.stock ? "In stock" : "Out of stock" }}</small>
                                                            </div>
                                                        </div>

                                                        <div v-if="it.m.text" class="tt-msg-text">{{ it.m.text }}</div>

                                                        <div v-if="it.m.attachments && it.m.attachments.length" class="tt-attach-list">
                                                            <template v-for="a in it.m.attachments" :key="a.name">
                                                                <img v-if="a.url && a.isImage" :src="a.url" :alt="a.name" class="tt-attach-img" />
                                                                <span v-else class="tt-attach-file"><v-icon size="14">mdi-file-outline</v-icon>{{ a.name }}</span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    <div v-if="it.last" class="tt-msg-meta">
                                                        {{ msgTime(it.m.at) }}
                                                        <template v-if="it.m.from === 'agent'">
                                                            <v-icon v-if="it.m.status === 'failed'" size="14" color="#dc2626" class="ml-1">mdi-alert-circle-outline</v-icon>
                                                            <a v-if="it.m.status === 'failed'" class="tt-retry" @click="retry(it.m)">Not sent · Retry</a>
                                                            <v-icon v-else size="15" :color="it.m.status === 'read' ? '#2f5be7' : '#9db3ae'" class="ml-1">{{ it.m.status === "sent" ? "mdi-check" : "mdi-check-all" }}</v-icon>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <button v-if="!atBottom" class="tt-scroll-btn" aria-label="Jump to latest message" @click="scrollBottom(true)"><v-icon size="18">mdi-chevron-down</v-icon></button>
                                </div>

                                <!-- Composer: one slim box, tools live in a single footer row -->
                                <div class="tt-composer-wrap">
                                    <div v-if="replyMode === 'reply' && composerState === 'free' && !draft && !attachments.length" class="tt-suggest">
                                        <span v-if="aiSuggestion" class="tt-ai-chip" role="button" tabindex="0" @click="insertQuick(aiSuggestion)" @keydown.enter="insertQuick(aiSuggestion)">
                                            <v-icon size="13">mdi-creation</v-icon>Suggested · {{ aiSuggestion.label }}
                                        </span>
                                        <button v-for="q in quickReplies.slice(0, 5)" :key="q.label" @click="insertQuick(q)">{{ q.label }}</button>
                                    </div>

                                    <div v-if="slashMatches.length" class="tt-slash">
                                        <div v-for="q in slashMatches" :key="q.label" class="tt-slash-item" @click="insertQuick(q, true)">
                                            <b>/{{ q.label.toLowerCase() }}</b><span>{{ q.text }}</span>
                                        </div>
                                    </div>

                                    <div class="tt-composer" :class="{ 'tt-composer--note': replyMode === 'note', drag: dragging }" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
                                        <div v-if="composerState === 'blocked'" class="tt-composer-banner">
                                            <v-icon size="18" color="#8a5a00">mdi-clock-alert-outline</v-icon>
                                            <span>The 24-hour reply window for {{ platforms[active.platform].label }} has closed. You can reply again as soon as the customer messages you, or leave an internal note.</span>
                                        </div>

                                        <template v-else-if="composerState === 'template'">
                                            <div class="tt-composer-banner">
                                                <v-icon size="18" color="#8a5a00">mdi-clock-alert-outline</v-icon>
                                                <span>The 24-hour WhatsApp window has closed. Send an approved template to restart the conversation.</span>
                                            </div>
                                            <div class="d-flex align-center ga-2 mt-3">
                                                <v-select v-model="templatePick" :items="waTemplates" item-title="name" item-value="name" hide-details placeholder="Choose a template" :menu-props="overlayMenu" />
                                                <v-btn color="#0f9d6b" variant="flat" class="text-none tt-send-btn" :disabled="!templatePick" prepend-icon="mdi-send" @click="sendTemplate">Send template</v-btn>
                                            </div>
                                            <div v-if="templatePick" class="tt-tpl-preview">{{ templateBody }}</div>
                                        </template>

                                        <template v-else>
                                            <v-text-field v-if="active.platform === 'email' && replyMode === 'reply'" v-model="emailSubject" hide-details prepend-inner-icon="mdi-format-title" placeholder="Subject" class="mb-1" />
                                            <v-textarea
                                                v-model="draft"
                                                variant="plain"
                                                rows="1"
                                                auto-grow
                                                max-rows="6"
                                                hide-details
                                                class="tt-textarea"
                                                :placeholder="replyMode === 'note' ? 'Write a private note for your team…' : 'Reply via ' + platforms[active.platform].label + '…  Enter to send · / for quick replies'"
                                                @keydown.enter.exact.prevent="send"
                                            />
                                            <div v-if="attachments.length" class="tt-thumbs">
                                                <div v-for="(f, i) in attachments" :key="f.name + i" class="tt-thumb">
                                                    <img v-if="f.type.startsWith('image/')" :src="thumbUrl(f)" :alt="f.name" />
                                                    <span v-else><v-icon size="18">mdi-file-outline</v-icon><small>{{ f.name }}</small></span>
                                                    <button :aria-label="'Remove ' + f.name" @click="attachments.splice(i, 1)"><v-icon size="12" color="white">mdi-close</v-icon></button>
                                                </div>
                                            </div>
                                        </template>

                                        <div class="tt-toolbar">
                                            <div class="tt-mode" role="tablist" aria-label="Reply mode">
                                                <button :class="{ on: replyMode === 'reply' }" role="tab" :aria-selected="replyMode === 'reply'" @click="replyMode = 'reply'"><v-icon size="14">mdi-reply-outline</v-icon>Reply</button>
                                                <button :class="{ on: replyMode === 'note' }" role="tab" :aria-selected="replyMode === 'note'" @click="replyMode = 'note'"><v-icon size="14">mdi-note-text-outline</v-icon>Note</button>
                                            </div>
                                            <div v-if="active.platform === 'fbcomment' && replyMode === 'reply'" class="tt-seg-mini">
                                                <button :class="{ on: commentMode === 'public' }" @click="commentMode = 'public'">Public</button>
                                                <button :class="{ on: commentMode === 'private' }" @click="commentMode = 'private'">Private</button>
                                            </div>

                                            <template v-if="composerState === 'free'">
                                                <span class="tt-tool-sep"></span>
                                                <v-menu location="top" content-class="tt-overlay">
                                                    <template #activator="{ props }">
                                                        <v-btn v-bind="props" size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-flash-outline">Replies</v-btn>
                                                    </template>
                                                    <v-list density="compact" max-width="340">
                                                        <v-list-item v-for="q in quickReplies" :key="q.label" :title="q.label" :subtitle="q.text" @click="insertQuick(q)" />
                                                    </v-list>
                                                </v-menu>
                                                <v-btn v-if="replyMode === 'reply' && aiSuggestion" size="small" variant="text" class="text-none tt-tool tt-tool-ai" prepend-icon="mdi-creation" @click="insertQuick(aiSuggestion, true)">Suggest</v-btn>
                                                <v-menu location="top" :close-on-content-click="false" content-class="tt-overlay">
                                                    <template #activator="{ props }">
                                                        <v-btn v-bind="props" icon variant="text" size="small" class="tt-tool" aria-label="Emoji"><v-icon size="19">mdi-emoticon-happy-outline</v-icon></v-btn>
                                                    </template>
                                                    <div class="tt-emoji-pop">
                                                        <button v-for="e in emojis" :key="e" @click="draft += e">{{ e }}</button>
                                                    </div>
                                                </v-menu>
                                                <v-btn icon variant="text" size="small" class="tt-tool" aria-label="Attach files" @click="$refs.fileInput.click()"><v-icon size="19">mdi-paperclip</v-icon></v-btn>
                                                <input ref="fileInput" type="file" multiple hidden @change="onFiles" />
                                                <v-menu location="top" :close-on-content-click="false" content-class="tt-overlay">
                                                    <template #activator="{ props }">
                                                        <v-btn v-bind="props" size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-package-variant-closed">Product</v-btn>
                                                    </template>
                                                    <div class="tt-prod-pop">
                                                        <v-text-field v-model="productSearch" hide-details clearable placeholder="Search products" prepend-inner-icon="mdi-magnify" />
                                                        <div class="tt-prod-pop-list">
                                                            <div v-for="p in productResults" :key="p.slug" class="tt-prod-pop-item" @click="shareProduct(p)">
                                                                <div><b>{{ p.name }}</b><span>{{ money(p.price) }}</span></div>
                                                                <small :class="p.stock ? 'in' : 'out'">{{ p.stock ? p.stock + " in stock" : "Out of stock" }}</small>
                                                            </div>
                                                            <div v-if="!productResults.length" class="tt-sub pa-3">No products found</div>
                                                        </div>
                                                    </div>
                                                </v-menu>
                                            </template>

                                            <v-spacer />
                                            <template v-if="composerState === 'free'">
                                                <span v-if="active.platform === 'sms' && replyMode === 'reply'" class="tt-sub mr-2">{{ draft.length }} chars · {{ smsParts }} SMS</span>
                                                <v-btn :color="replyMode === 'note' ? '#d97706' : '#0f9d6b'" variant="flat" size="small" class="text-none tt-send-btn" :disabled="!draft.trim() && !attachments.length" :prepend-icon="replyMode === 'note' ? 'mdi-note-plus-outline' : 'mdi-send'" @click="send">
                                                    {{ replyMode === "note" ? "Add note" : "Send" }}
                                                </v-btn>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-else class="tt-empty-thread">
                                <div class="tt-empty-ic"><v-icon size="34" color="#0f9d6b">mdi-forum-outline</v-icon></div>
                                <div class="tt-empty-title">Select a conversation</div>
                                <div class="tt-sub">Pick a chat on the left to read the full history and reply.</div>
                                <div class="tt-empty-chans">
                                    <span v-for="(p, key) in platforms" :key="key" :style="{ background: p.color, opacity: channels[key].connected ? 1 : 0.3 }" :title="p.label"><v-icon size="15" color="white">{{ p.icon }}</v-icon></span>
                                </div>
                            </div>
                        </section>

                        <!-- ───── Customer panel ───── -->
                        <aside v-if="active" class="tt-pane tt-info-pane">
                            <div class="tt-info-head">
                                <div class="tt-head-ic"><v-icon size="16">mdi-account-details-outline</v-icon></div>
                                <span class="tt-info-title ml-2">Customer details</span>
                                <v-spacer />
                                <v-btn icon size="x-small" variant="text" aria-label="Close panel" @click="showInfo = false"><v-icon size="16">mdi-close</v-icon></v-btn>
                            </div>
                            <div class="tt-info-scroll">
                                <div class="tt-profile">
                                    <div class="tt-avatar tt-avatar--lg" :style="{ background: avatarTint(active.customer) }">{{ initials(active.customer) }}</div>
                                    <div class="tt-info-name">{{ active.customer }}</div>
                                    <div class="tt-sub">{{ active.handle }}</div>
                                    <span class="tt-chip mt-2" :class="'tone-' + tierTone">{{ tier }} customer</span>
                                    <div class="tt-quick-actions">
                                        <a v-if="active.phone" :href="'tel:' + active.phone" title="Call" aria-label="Call"><v-icon size="17">mdi-phone-outline</v-icon></a>
                                        <a v-if="active.email" :href="'mailto:' + active.email" title="Email" aria-label="Email"><v-icon size="17">mdi-email-outline</v-icon></a>
                                        <router-link to="/admin/create-order" title="Create order" aria-label="Create order"><v-icon size="17">mdi-cart-plus</v-icon></router-link>
                                        <router-link to="/admin/customers" title="View customer" aria-label="View customer"><v-icon size="17">mdi-account-outline</v-icon></router-link>
                                    </div>
                                </div>

                                <div class="tt-info-tiles">
                                    <div class="tt-info-tile" style="--c: #2f5be7"><div class="tt-sub">Orders</div><div class="tt-tile-value">{{ active.orderCount }}</div></div>
                                    <div class="tt-info-tile" style="--c: #0f9d6b"><div class="tt-sub">Lifetime value</div><div class="tt-tile-value">{{ money(active.lifetime) }}</div></div>
                                </div>

                                <div class="tt-ai-card">
                                    <span class="tt-ai-tag"><v-icon size="13">mdi-creation</v-icon>AI insight</span>
                                    <p>{{ customerInsight }}</p>
                                </div>

                                <div class="tt-info-rows">
                                    <div v-if="active.phone"><v-icon size="15">mdi-phone-outline</v-icon>{{ active.phone }}</div>
                                    <div v-if="active.email"><v-icon size="15">mdi-email-outline</v-icon>{{ active.email }}</div>
                                    <div v-if="active.city"><v-icon size="15">mdi-map-marker-outline</v-icon>{{ active.city }}</div>
                                    <div><v-icon size="15">mdi-calendar-outline</v-icon>Customer since {{ active.since }}</div>
                                </div>

                                <div class="tt-field-label">Recent orders</div>
                                <div v-for="o in active.orders" :key="o.id" class="tt-order-row">
                                    <span class="tt-strong">{{ o.id }}</span>
                                    <v-spacer />
                                    <span class="tt-chip" :class="'tone-' + (tones[o.status] || 'gray')">{{ o.status }}</span>
                                    <span class="tt-sub ml-2">{{ money(o.total) }}</span>
                                </div>
                                <div v-if="!active.orders.length" class="tt-sub">No orders yet. This is a new lead.</div>

                                <div class="tt-field-label">Tags</div>
                                <div class="d-flex flex-wrap ga-1 mb-2">
                                    <span v-for="t in active.tags" :key="t" class="tt-tagchip">{{ t }}<button :aria-label="'Remove ' + t" @click="removeTag(t)"><v-icon size="12">mdi-close</v-icon></button></span>
                                </div>
                                <v-text-field v-model="newTag" hide-details placeholder="Add a tag, press Enter" @keydown.enter.prevent="addTag" />

                                <div class="tt-field-label">Pinned notes</div>
                                <v-textarea v-model="active.notes" rows="3" auto-grow hide-details placeholder="Anything the team should remember about this customer" />

                                <div class="tt-field-label">Conversation</div>
                                <div class="tt-info-rows">
                                    <div><v-icon size="15">mdi-message-outline</v-icon>{{ active.messages.filter((m) => m.from !== "system" && m.from !== "note").length }} messages</div>
                                    <div><v-icon size="15">mdi-clock-start</v-icon>Started {{ relTime(active.messages[0].at) }} ago</div>
                                    <div><v-icon size="15" :color="platforms[active.platform].color">{{ platforms[active.platform].icon }}</v-icon>via {{ platforms[active.platform].label }}</div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </v-card>

            <!-- ═════════ Channels dialog ═════════ -->
            <v-dialog v-model="channelDialog" max-width="760" content-class="tt-overlay">
                <div class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg"><v-icon size="20">mdi-plug</v-icon></div>
                        <div class="ml-3">
                            <div class="tt-info-title">Connected channels</div>
                            <div class="tt-sub">{{ connectedCount }} of {{ totalChannels }} live. Connect every platform your customers message you on.</div>
                        </div>
                        <v-spacer />
                        <v-btn icon size="small" variant="text" aria-label="Close" @click="channelDialog = false"><v-icon size="18">mdi-close</v-icon></v-btn>
                    </div>
                    <div class="tt-dialog-grid">
                        <div v-for="(p, key) in platforms" :key="key" class="tt-ch-card" :class="{ live: channels[key].connected }">
                            <span class="tt-ch-ic" :style="{ background: p.color }"><v-icon size="22" color="white">{{ p.icon }}</v-icon></span>
                            <div class="tt-ch-main">
                                <b>{{ p.label }}</b>
                                <span>{{ channels[key].connected ? channels[key].account : p.hint }}</span>
                            </div>
                            <v-btn size="small" :variant="channels[key].connected ? 'outlined' : 'flat'" :color="channels[key].connected ? undefined : '#0f9d6b'" class="text-none tt-ch-btn" @click="toggleChannel(key)">
                                {{ channels[key].connected ? "Disconnect" : "Connect" }}
                            </v-btn>
                        </div>
                    </div>
                    <div class="tt-sub tt-dialog-foot">
                        Messenger, Instagram and Facebook comments connect through one Meta Business app. WhatsApp uses the WhatsApp Business Cloud API. Your server receives each platform's webhook and exposes the messages to this page.
                    </div>
                </div>
            </v-dialog>

            <v-snackbar v-model="snackbar" color="#0a5548" timeout="3500">{{ snackbarText }}</v-snackbar>
        </div>
    </v-defaults-provider>
</template>

<script>
let seq = 0;
const msg = (from, text, at, extra = {}) => ({ id: ++seq, from, text, at, status: from === "agent" ? "read" : undefined, ...extra });
const mins = (m) => Date.now() - m * 60000;
const hrs = (h) => mins(h * 60);

/* Same colour tokens as the Dashboard */
const C = { teal: "#0f9d6b", blue: "#2f5be7", violet: "#7c3aed", amber: "#f59e0b", coral: "#ef6a45", rose: "#ec4899" };

const PLATFORMS = {
    messenger: { label: "Messenger", icon: "mdi-facebook-messenger", color: "#0084ff", windowed: true, hint: "Facebook Page inbox" },
    instagram: { label: "Instagram", icon: "mdi-instagram", color: "#e1306c", windowed: true, hint: "Instagram professional account DMs" },
    whatsapp: { label: "WhatsApp", icon: "mdi-whatsapp", color: "#25d366", windowed: true, hint: "WhatsApp Business Cloud API number" },
    fbcomment: { label: "FB comments", icon: "mdi-comment-text-outline", color: "#1877f2", windowed: false, hint: "Comments on your Facebook page posts" },
    telegram: { label: "Telegram", icon: "mdi-telegram", color: "#229ed9", windowed: false, hint: "Telegram bot" },
    email: { label: "Email", icon: "mdi-email-outline", color: "#d97706", windowed: false, hint: "support@ mailbox (IMAP / SMTP)" },
    sms: { label: "SMS", icon: "mdi-message-text-outline", color: "#7c3aed", windowed: false, hint: "SMS gateway" },
    web: { label: "Website chat", icon: "mdi-chat-processing-outline", color: "#0f766e", windowed: false, hint: "Live chat widget on your store" },
};
const PRODUCTS = [
    { name: "Anker PowerCore 20000", slug: "anker-powercore-20000", price: 4200, stock: 3 },
    { name: "Baseus Bowie E9 Earbuds", slug: "baseus-e9", price: 2350, stock: 12 },
    { name: "Xiaomi Smart Band 8", slug: "xiaomi-band-8", price: 3150, stock: 0 },
    { name: "Baseus 65W GaN Charger", slug: "baseus-65w", price: 3290, stock: 11 },
    { name: "TP-Link Archer AX55", slug: "archer-ax55", price: 8900, stock: 24 },
    { name: "SanDisk Extreme SSD 1TB", slug: "sandisk-1tb", price: 11500, stock: 7 },
];
const TINTS = ["#dff5ec", "#e6edff", "#efe8ff", "#fff1d1", "#fde6f1", "#dcf3f0"];
const WINDOW_MS = 24 * 3600 * 1000;
const LATE_MS = 3600000;

export default {
    name: "MessageCenter",

    data() {
        return {
            overlayMenu: { contentClass: "tt-overlay" },
            fieldDefaults: {
                VTextField: { variant: "outlined", density: "compact" },
                VSelect: { variant: "outlined", density: "compact" },
                VTextarea: { variant: "outlined", density: "compact" },
            },

            me: "Mitu Akter", // replace with the signed-in staff member
            staff: ["Tanjil Ahmed", "Mitu Akter", "Shakil Rana", "Imran Hossain"],
            platforms: PLATFORMS,
            tones: { Pending: "amber", Processing: "blue", Delivered: "green", Cancelled: "red" },
            channels: {
                messenger: { connected: true, account: "Tanjil Traders (Page)" },
                instagram: { connected: true, account: "@tanjiltraders" },
                whatsapp: { connected: true, account: "+880 1711-000000" },
                fbcomment: { connected: true, account: "Tanjil Traders (Page)" },
                telegram: { connected: false, account: "" },
                email: { connected: true, account: "support@tanjiltraders.com" },
                sms: { connected: true, account: "Sender ID: TANJIL" },
                web: { connected: true, account: "tanjiltraders.com widget" },
            },

            scope: "team",
            scopes: [{ value: "team", label: "Team" }, { value: "mine", label: "Mine" }, { value: "unassigned", label: "Unassigned" }],

            channelDialog: false,
            search: "",
            platformFilter: "all",
            statusFilter: "open",
            sortBy: "newest",
            activeId: null,
            showThread: false,
            showInfo: typeof window !== "undefined" ? window.innerWidth >= 1800 : true,

            replyMode: "reply",
            commentMode: "public",
            draft: "",
            emailSubject: "",
            attachments: [],
            dragging: false,
            atBottom: true,
            templatePick: null,
            productSearch: "",
            newTag: "",
            nowTs: Date.now(),
            timer: null,
            snackbar: false,
            snackbarText: "",

            statusTabs: [
                { value: "open", label: "Open" },
                { value: "unread", label: "Unread" },
                { value: "mine", label: "Mine" },
                { value: "resolved", label: "Done" },
            ],
            extraFilters: [
                { value: "unassigned", title: "Unassigned", icon: "mdi-account-question-outline" },
                { value: "starred", title: "Starred", icon: "mdi-star-outline" },
                { value: "snoozed", title: "Snoozed", icon: "mdi-alarm-snooze" },
            ],
            sortOptions: [
                { value: "newest", title: "Newest first" },
                { value: "oldest", title: "Longest waiting first" },
                { value: "unread", title: "Unread first" },
            ],
            snoozeOptions: [
                { label: "1 hour", ms: 3600000 },
                { label: "3 hours", ms: 3 * 3600000 },
                { label: "Tomorrow, 9am", ms: -1 },
                { label: "Next week", ms: 7 * 86400000 },
            ],

            emojis: ["😀", "😊", "😍", "🙏", "👍", "🙌", "🎉", "🔥", "✅", "❤️", "😅", "🤝", "📦", "🚚", "💳", "⭐", "😢", "🙂", "👌", "🎧", "🔋", "📱", "⌚", "🛒"],

            quickReplies: [
                { label: "Greeting", text: "Assalamu alaikum! Thanks for contacting Tanjil Traders. How can we help you today?" },
                { label: "In stock", text: "Yes, this item is in stock with official brand warranty. Would you like us to reserve one for you?" },
                { label: "Delivery", text: "We deliver inside Dhaka within 24 hours (৳70) and outside Dhaka in 2–4 days by courier. Cash on delivery is available up to ৳25,000." },
                { label: "Warranty", text: "All our devices come with official brand warranty. You can verify the IMEI / serial number with us before you buy." },
                { label: "Payment", text: "You can pay by bKash, Nagad, card or cash on delivery. Shall I send you the payment details?" },
                { label: "Showroom", text: "Our showroom is at House 32, Road 8/A, Dhanmondi, open 10am–8pm every day. You are welcome to try the product before buying." },
                { label: "Thanks", text: "Thank you for shopping with Tanjil Traders! Let us know if there is anything else we can do for you." },
            ],
            // approved WhatsApp templates: replace with the list from your WhatsApp Business account
            waTemplates: [
                { name: "enquiry_followup", body: "Hello! This is Tanjil Traders following up on your recent enquiry. Reply here and we will be happy to help." },
                { name: "back_in_stock", body: "Good news! The item you asked about is back in stock at Tanjil Traders. Reply YES and we will reserve one for you." },
                { name: "quotation_followup", body: "Hi! We have sent the quotation you requested. Would you like us to adjust quantities or delivery?" },
            ],
            products: PRODUCTS, // replace with GET /api/admin/products?search=

            // ── Sample conversations: replace with GET /api/admin/inbox ──
            conversations: [
                {
                    id: "c1", customer: "Nusrat Jahan", platform: "whatsapp", handle: "+880 1811-334455", phone: "+880 1811-334455", email: "nusrat.jahan@example.com",
                    city: "Uttara, Dhaka", since: "Mar 2026", lifetime: 22100, orderCount: 3, status: "open", assignee: "Mitu Akter", unread: 2, starred: true, tags: ["Power banks"], notes: "",
                    orders: [{ id: "#TT-10479", status: "Delivered", total: 7400 }, { id: "#TT-10391", status: "Delivered", total: 9600 }],
                    messages: [
                        msg("customer", "Assalamu alaikum, is the Anker PowerCore 20000 in stock?", mins(35)),
                        msg("agent", "Walaikum assalam! Yes, we have 3 left. Here are the details 👇", mins(31), { agent: "Mitu Akter", product: PRODUCTS[0] }),
                        msg("agent", "৳4,200 with official warranty.", mins(30), { agent: "Mitu Akter" }),
                        msg("customer", "Can you deliver to Uttara today?", mins(6)),
                        msg("customer", "And is cash on delivery available?", mins(5)),
                    ],
                },
                {
                    id: "c2", customer: "Sabbir Hossain", platform: "messenger", handle: "facebook.com/sabbir.h", phone: "+880 1611-778899", email: "",
                    city: "Khulshi, Chattogram", since: "Jun 2026", lifetime: 12900, orderCount: 1, status: "open", assignee: "", unread: 1, starred: false, tags: ["Wearables"], notes: "",
                    orders: [{ id: "#TT-10481", status: "Processing", total: 12900 }],
                    messages: [msg("customer", "Hi, what is the price of the Xiaomi Smart Band 8? Is it original?", mins(82))],
                },
                {
                    id: "c3", customer: "Rahim Traders", platform: "email", handle: "purchase@rahimtraders.com", phone: "+880 1711-223344", email: "purchase@rahimtraders.com",
                    subject: "Bulk order inquiry — 40 power banks", city: "Dhanmondi, Dhaka", since: "Nov 2025", lifetime: 186400, orderCount: 12, status: "open", assignee: "Tanjil Ahmed", unread: 0, starred: true, tags: ["Wholesale", "VIP"], notes: "Gets 8% wholesale rate on 20+ units.",
                    orders: [{ id: "#TT-10482", status: "Pending", total: 48500 }, { id: "#TT-10310", status: "Delivered", total: 61200 }],
                    messages: [
                        msg("customer", "Hello, we need 40 units of the Anker PowerCore 20000 for our shop. Please share your best wholesale price and delivery time to Dhanmondi.", hrs(27)),
                        msg("agent", "Dear Rahim Traders, for 40 units we can offer ৳3,860 per piece with official warranty and free delivery. Stock is available now, and we can dispatch within 24 hours.", hrs(26), { agent: "Tanjil Ahmed" }),
                        msg("note", "Checked with warehouse: 52 units available. OK to go down to ৳3,800 if they confirm today.", hrs(25), { agent: "Tanjil Ahmed" }),
                    ],
                },
                {
                    id: "c4", customer: "Ayesha Rahman", platform: "instagram", handle: "@ayesha.r", phone: "", email: "",
                    city: "Khulna", since: "Today", lifetime: 0, orderCount: 0, status: "open", assignee: "", unread: 1, starred: false, tags: ["New lead"], notes: "", orders: [],
                    messages: [msg("customer", "Do you have the black version of Baseus Bowie E9? Replied to your story 🙂", mins(48))],
                },
                {
                    id: "c5", customer: "Farhan Kabir", platform: "sms", handle: "+880 1711-887766", phone: "+880 1711-887766", email: "",
                    city: "Zindabazar, Sylhet", since: "Aug 2026", lifetime: 64200, orderCount: 5, status: "resolved", assignee: "Shakil Rana", unread: 0, starred: false, tags: [], notes: "",
                    orders: [{ id: "#TT-10477", status: "Delivered", total: 31200 }],
                    messages: [
                        msg("agent", "Your order #TT-10477 has been dispatched. Track: Steadfast SF-88231.", hrs(50), { agent: "Shakil Rana" }),
                        msg("customer", "Received, thank you!", hrs(48)),
                        msg("system", "Marked as resolved", hrs(48)),
                    ],
                },
                {
                    id: "c6", customer: "Website visitor #482", platform: "web", handle: "Live chat · /category/smartphones", phone: "", email: "",
                    city: "Dhaka", since: "Today", lifetime: 0, orderCount: 0, status: "open", assignee: "", unread: 1, starred: false, tags: [], notes: "", orders: [],
                    messages: [msg("customer", "Is the warranty official? Can I check the IMEI before I pay?", mins(3))],
                },
                {
                    id: "c7", customer: "Karim Electronics", platform: "whatsapp", handle: "+880 1911-223311", phone: "+880 1911-223311", email: "karim.electronics@example.com",
                    city: "Shaheb Bazar, Rajshahi", since: "Jan 2026", lifetime: 512000, orderCount: 18, status: "open", assignee: "Shakil Rana", unread: 0, starred: false, tags: ["Wholesale"], notes: "",
                    orders: [{ id: "#TT-10475", status: "Processing", total: 142500 }],
                    messages: [
                        msg("customer", "Please send the quotation for 30 smart bands.", hrs(30)),
                        msg("agent", "Sent to your email just now, please check.", hrs(29), { agent: "Shakil Rana" }),
                    ],
                },
                {
                    id: "c8", customer: "Tania Islam", platform: "messenger", handle: "facebook.com/tania.islam", phone: "+880 1511-667788", email: "",
                    city: "Kotwali, Barishal", since: "Sep 2026", lifetime: 5600, orderCount: 1, status: "open", assignee: "Imran Hossain", unread: 0, starred: false, tags: [], notes: "",
                    orders: [{ id: "#TT-10474", status: "Pending", total: 5600 }],
                    messages: [
                        msg("customer", "When will my order #TT-10474 be confirmed?", hrs(72)),
                        msg("agent", "We are confirming it today. You will get an SMS shortly.", hrs(71), { agent: "Imran Hossain" }),
                        msg("agent", "Your order is confirmed and will ship tomorrow.", mins(20), { agent: "Imran Hossain", status: "failed" }),
                    ],
                },
                {
                    id: "c9", customer: "Mehedi Hasan", platform: "fbcomment", handle: "Comment on Eid Offer post", phone: "", email: "", postTitle: "Eid Offer — up to 30% off power banks & chargers",
                    city: "", since: "Today", lifetime: 0, orderCount: 0, status: "open", assignee: "", unread: 1, starred: false, tags: ["Comment"], notes: "", orders: [],
                    messages: [msg("customer", "Price koto Baseus 65W charger er? Inbox e janaben please", mins(14))],
                },
            ],
        };
    },

    computed: {
        activeChannel() {
            return this.platformFilter === "all" ? { label: "All inboxes", icon: "mdi-inbox-multiple", color: "#0f9d6b" } : PLATFORMS[this.platformFilter];
        },
        totalChannels() {
            return Object.keys(PLATFORMS).length;
        },
        connectedCount() {
            return Object.values(this.channels).filter((c) => c.connected).length;
        },
        active() {
            return this.conversations.find((c) => c.id === this.activeId) || null;
        },
        totalUnread() {
            return this.conversations.reduce((a, c) => a + (c.unread || 0), 0);
        },
        unreadConvos() {
            return this.conversations.filter((c) => c.unread > 0).length;
        },
        openCount() {
            return this.conversations.filter((c) => c.status === "open").length;
        },
        unassignedCount() {
            return this.conversations.filter((c) => c.status === "open" && !c.assignee).length;
        },
        overdueCount() {
            return this.conversations.filter((c) => this.isLate(c)).length;
        },
        slaPct() {
            return this.openCount ? Math.round(((this.openCount - this.overdueCount) / this.openCount) * 100) : 100;
        },
        heroStats() {
            return [
                { icon: "mdi-email-unread-outline", color: C.teal, value: this.unreadConvos, label: "Unread chats", note: this.totalUnread + " new messages", tag: "Live" },
                { icon: "mdi-account-question-outline", color: C.amber, value: this.unassignedCount, label: "Unassigned", note: "Waiting for an owner", tag: this.unassignedCount ? "Assign" : "Clear" },
                { icon: "mdi-timer-alert-outline", color: C.coral, value: this.overdueCount, label: "Waiting over 1 hour", note: this.slaPct + "% answered in time", tag: this.overdueCount ? "Reply" : "On track", progress: this.slaPct },
                { icon: "mdi-lightning-bolt-outline", color: C.blue, value: "4m", label: "Average first reply", note: "Target under 5 minutes", tag: "On track", progress: 82 },
            ];
        },
        unreadByPlatform() {
            const out = {};
            this.conversations.forEach((c) => {
                if (c.unread) out[c.platform] = (out[c.platform] || 0) + c.unread;
            });
            return out;
        },
        countByPlatform() {
            const out = {};
            this.conversations.forEach((c) => (out[c.platform] = (out[c.platform] || 0) + 1));
            return out;
        },
        statusCounts() {
            const inPlatform = this.conversations.filter((c) => this.platformFilter === "all" || c.platform === this.platformFilter);
            return {
                open: inPlatform.filter((c) => c.status === "open" && !this.isSnoozed(c)).length,
                unread: inPlatform.filter((c) => c.unread > 0).length,
                mine: inPlatform.filter((c) => c.assignee === this.me && c.status === "open").length,
                resolved: inPlatform.filter((c) => c.status === "resolved").length,
            };
        },
        filteredConversations() {
            const q = (this.search || "").trim().toLowerCase();
            const list = this.conversations.filter((c) => {
                if (this.platformFilter !== "all" && c.platform !== this.platformFilter) return false;
                const st = this.statusFilter;
                if (st === "open" && !(c.status === "open" && !this.isSnoozed(c))) return false;
                if (st === "unread" && !(c.unread > 0)) return false;
                if (st === "mine" && !(c.assignee === this.me && c.status === "open")) return false;
                if (st === "unassigned" && !(c.status === "open" && !c.assignee)) return false;
                if (st === "starred" && !c.starred) return false;
                if (st === "snoozed" && !this.isSnoozed(c)) return false;
                if (st === "resolved" && c.status !== "resolved") return false;
                if (q && !(c.customer.toLowerCase().includes(q) || (c.phone || "").includes(q) || this.previewText(c).toLowerCase().includes(q))) return false;
                return true;
            });
            const last = (c) => this.lastMsg(c).at;
            if (this.sortBy === "oldest") return list.sort((a, b) => last(a) - last(b));
            if (this.sortBy === "unread") return list.sort((a, b) => (b.unread > 0) - (a.unread > 0) || last(b) - last(a));
            return list.sort((a, b) => last(b) - last(a));
        },
        assigneeItems() {
            return [{ title: "Unassigned", value: "" }, ...this.staff.map((s) => ({ title: s, value: s }))];
        },
        activeAssignee: {
            get() {
                return this.active ? this.active.assignee || "" : "";
            },
            set(v) {
                this.assign(v);
            },
        },
        tier() {
            const l = this.active ? this.active.lifetime : 0;
            return l >= 150000 ? "VIP" : l >= 20000 ? "Regular" : "New";
        },
        tierTone() {
            return { VIP: "violet", Regular: "blue", New: "teal" }[this.tier];
        },
        // plain-language summary of the customer, built from the data we already have
        customerInsight() {
            const c = this.active;
            if (!c) return "";
            if (!c.orderCount) return "New lead with no orders yet. A quick, friendly answer with a product link gives the best chance of a first sale.";
            const avg = Math.round(c.lifetime / c.orderCount);
            const open = c.orders.filter((o) => o.status === "Pending" || o.status === "Processing").length;
            return `${c.orderCount} order${c.orderCount > 1 ? "s" : ""} worth ${this.money(c.lifetime)}, about ${this.money(avg)} each.` + (open ? ` ${open} order${open > 1 ? "s are" : " is"} still in progress, so expect delivery questions.` : " Nothing is in progress right now.");
        },
        // groups consecutive messages from the same sender so the chat reads like a real conversation
        threadItems() {
            const out = [];
            let lastDay = "";
            ((this.active && this.active.messages) || []).forEach((m) => {
                const day = new Date(m.at).toDateString();
                if (day !== lastDay) {
                    out.push({ id: "d" + m.id, divider: true, label: this.dayLabel(m.at) });
                    lastDay = day;
                }
                out.push({ id: m.id, m, sys: m.from === "system" });
            });
            const key = (e) => (e && e.m && !e.sys && !e.divider ? e.m.from + "|" + (e.m.agent || "") : null);
            out.forEach((e, i) => {
                if (!e.m || e.sys) return;
                const p = out[i - 1];
                const n = out[i + 1];
                e.first = key(p) !== key(e) || e.m.at - p.m.at > 300000;
                e.last = key(n) !== key(e) || n.m.at - e.m.at > 300000;
            });
            return out;
        },
        // WhatsApp, Messenger and Instagram only allow free-form replies for 24h after the customer's last message
        windowInfo() {
            const c = this.active;
            if (!c || !PLATFORMS[c.platform].windowed) return { applies: false };
            const lastCustomer = [...c.messages].reverse().find((m) => m.from === "customer");
            const left = (lastCustomer ? lastCustomer.at : 0) + WINDOW_MS - this.nowTs;
            const h = Math.floor(left / 3600000);
            const m = Math.floor((left % 3600000) / 60000);
            return { applies: true, open: left > 0, label: left > 0 ? `${h}h ${m}m left` : "Closed" };
        },
        composerState() {
            if (!this.active || this.replyMode === "note") return "free";
            if (this.windowInfo.applies && !this.windowInfo.open) return this.active.platform === "whatsapp" ? "template" : "blocked";
            return "free";
        },
        // picks the saved reply that best fits the customer's latest message
        aiSuggestion() {
            const c = this.active;
            if (!c) return null;
            const last = [...c.messages].reverse().find((m) => m.from === "customer");
            if (!last || this.lastMsg(c).from !== "customer") return null;
            const t = (last.text || "").toLowerCase();
            const rules = [
                [/deliver|shipping|courier|today|dispatch/, "Delivery"],
                [/warranty|imei|original|official|genuine/, "Warranty"],
                [/cash|bkash|nagad|pay|card/, "Payment"],
                [/stock|available|have the|do you have/, "In stock"],
                [/showroom|address|visit|location/, "Showroom"],
                [/price|koto|how much/, "In stock"],
            ];
            const hit = rules.find(([re]) => re.test(t));
            return this.quickReplies.find((q) => q.label === (hit ? hit[1] : "Greeting")) || null;
        },
        templateBody() {
            const t = this.waTemplates.find((x) => x.name === this.templatePick);
            return t ? t.body : "";
        },
        slashMatches() {
            if (!this.draft.startsWith("/") || this.draft.includes(" ")) return [];
            const q = this.draft.slice(1).toLowerCase();
            return this.quickReplies.filter((r) => r.label.toLowerCase().includes(q)).slice(0, 4);
        },
        productResults() {
            const q = (this.productSearch || "").toLowerCase();
            return this.products.filter((p) => !q || p.name.toLowerCase().includes(q));
        },
        smsParts() {
            return Math.max(1, Math.ceil(this.draft.length / 160));
        },
    },

    watch: {
        "$route.params.conversationId"(id) {
            this.openFromRoute(id);
        },
    },

    mounted() {
        this.timer = setInterval(() => (this.nowTs = Date.now()), 30000);
        this.openFromRoute(this.$route && this.$route.params ? this.$route.params.conversationId : null);
        // this.loadInbox()  // GET /api/admin/inbox  (+ websocket / polling for new messages)
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },

    methods: {
        money: (v) => "৳ " + Number(v || 0).toLocaleString("en-IN"),
        initials(name) {
            return (name || "?").replace(/#/g, "").split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join("").toUpperCase();
        },
        avatarTint(name) {
            return TINTS[(name || "?").charCodeAt(0) % TINTS.length];
        },
        lastMsg(c) {
            return c.messages[c.messages.length - 1];
        },
        isLate(c) {
            return c.status === "open" && !this.isSnoozed(c) && this.lastMsg(c).from === "customer" && this.nowTs - this.lastMsg(c).at > LATE_MS;
        },
        previewText(c) {
            const m = [...c.messages].reverse().find((x) => x.from !== "system" && x.from !== "note") || this.lastMsg(c);
            return (m.from === "agent" ? "You: " : "") + (m.text || (m.product ? "🛍 " + m.product.name : m.attachments ? "📎 Attachment" : ""));
        },
        isSnoozed(c) {
            return !!c.snoozedUntil && c.snoozedUntil > this.nowTs;
        },
        relTime(ts) {
            const m = Math.floor((this.nowTs - ts) / 60000);
            if (m < 1) return "now";
            if (m < 60) return m + "m";
            const h = Math.floor(m / 60);
            return h < 24 ? h + "h" : Math.floor(h / 24) + "d";
        },
        msgTime(ts) {
            return new Date(ts).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
        },
        dayLabel(ts) {
            const d = new Date(ts);
            if (d.toDateString() === new Date(this.nowTs).toDateString()) return "Today";
            if (d.toDateString() === new Date(this.nowTs - 86400000).toDateString()) return "Yesterday";
            return d.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },
        thumbUrl(f) {
            if (!f._url) f._url = URL.createObjectURL(f);
            return f._url;
        },
        notify(text) {
            this.snackbarText = text;
            this.snackbar = true;
        },

        setScope(v) {
            this.scope = v;
            this.statusFilter = v === "team" ? "open" : v;
        },
        chanClick(key) {
            if (!this.channels[key].connected) {
                this.channelDialog = true;
                return;
            }
            this.platformFilter = key;
        },
        openFromRoute(id) {
            const c = id ? this.conversations.find((x) => String(x.id) === String(id)) : null;
            if (c) {
                this.statusFilter = c.status === "resolved" ? "resolved" : "open";
                this.selectConversation(c);
            }
        },
        selectConversation(c) {
            this.activeId = c.id;
            c.unread = 0; // PATCH /api/admin/inbox/:id/read
            this.showThread = true;
            this.draft = "";
            this.attachments = [];
            this.templatePick = null;
            this.replyMode = "reply";
            this.emailSubject = c.subject ? "Re: " + c.subject : "";
            this.scrollBottom();
        },
        scrollBottom(smooth = false) {
            this.$nextTick(() => {
                const el = this.$refs.threadBody;
                if (el) el.scrollTo({ top: el.scrollHeight, behavior: smooth ? "smooth" : "auto" });
            });
        },
        onScroll(e) {
            const el = e.target;
            this.atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 80;
        },
        pushMessage(m) {
            const c = this.active;
            const message = msg(m.from, m.text, Date.now(), m);
            c.messages.push(message);
            c.unread = 0;
            if (m.from === "agent") {
                if (c.status === "resolved") c.status = "open";
                if (!c.assignee) c.assignee = this.me;
                // simulated receipts: real statuses come from the platform webhooks
                message.status = "sent";
                setTimeout(() => (message.status = "delivered"), 900);
            }
            this.scrollBottom(true);
            return message;
        },

        send() {
            const text = this.draft.trim();
            if ((!text && !this.attachments.length) || this.composerState !== "free") return;
            const files = this.attachments.map((f) => ({ name: f.name, isImage: f.type.startsWith("image/"), url: f.type.startsWith("image/") ? this.thumbUrl(f) : null }));
            // POST /api/admin/inbox/:id/messages  (FormData: text, files[], mode, subject, public)
            if (this.replyMode === "note") {
                this.pushMessage({ from: "note", text, agent: this.me, attachments: files });
            } else {
                const extra = { from: "agent", text, agent: this.me, attachments: files };
                if (this.active.platform === "fbcomment" && this.commentMode === "private") extra.public = false;
                this.pushMessage(extra);
            }
            this.draft = "";
            this.attachments = [];
        },
        sendTemplate() {
            if (!this.templatePick) return;
            // POST /api/admin/inbox/:id/template { name }
            this.pushMessage({ from: "agent", text: this.templateBody, agent: this.me, template: this.templatePick });
            this.templatePick = null;
        },
        retry(m) {
            m.status = "sent";
            setTimeout(() => (m.status = "delivered"), 900);
        },
        insertQuick(q, replace = false) {
            this.draft = replace || !this.draft ? q.text : this.draft + " " + q.text;
        },
        shareProduct(p) {
            // sends a rich product card; any text already typed goes along as the caption
            this.pushMessage({ from: "agent", text: this.draft.trim(), agent: this.me, product: p });
            this.draft = "";
        },
        onFiles(e) {
            this.attachments.push(...Array.from(e.target.files || []));
            e.target.value = "";
        },
        onDrop(e) {
            this.dragging = false;
            this.attachments.push(...Array.from(e.dataTransfer.files || []));
        },

        assign(name) {
            const c = this.active;
            if (!c || (c.assignee || "") === name) return;
            c.assignee = name;
            c.messages.push(msg("system", name ? `Assigned to ${name}` : "Unassigned", Date.now()));
            this.scrollBottom();
        },
        toggleResolved() {
            const c = this.active;
            c.status = c.status === "resolved" ? "open" : "resolved";
            c.messages.push(msg("system", c.status === "resolved" ? "Marked as resolved" : "Reopened", Date.now()));
            this.scrollBottom();
        },
        snooze(opt) {
            const c = this.active;
            let until = this.nowTs + opt.ms;
            if (opt.ms === -1) {
                const d = new Date(this.nowTs + 86400000);
                d.setHours(9, 0, 0, 0);
                until = d.getTime();
            }
            c.snoozedUntil = until;
            c.messages.push(msg("system", `Snoozed until ${new Date(until).toLocaleString("en-GB", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" })}`, Date.now()));
            this.notify("Snoozed. It returns to Open when the time is up or the customer replies.");
        },
        markUnread() {
            this.active.unread = 1;
            this.showThread = false;
            this.activeId = null;
        },
        markSpam() {
            this.active.status = "resolved";
            this.active.tags.push("Spam");
            this.notify("Marked as spam and resolved.");
        },
        markAllRead() {
            this.conversations.forEach((c) => (c.unread = 0));
            this.notify("All conversations marked as read.");
        },
        addTag() {
            const t = this.newTag.trim();
            if (t && !this.active.tags.includes(t)) this.active.tags.push(t);
            this.newTag = "";
        },
        removeTag(t) {
            this.active.tags = this.active.tags.filter((x) => x !== t);
        },
        toggleChannel(key) {
            const ch = this.channels[key];
            ch.connected = !ch.connected;
            if (ch.connected && !ch.account) ch.account = "Connected just now";
            if (!ch.connected && this.platformFilter === key) this.platformFilter = "all";
            // real flow: redirect to the platform's OAuth / embedded signup, then save the token on your server
            this.notify(`${PLATFORMS[key].label} ${ch.connected ? "connected" : "disconnected"}.`);
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

/* Same tokens as the Dashboard. Vuetify colour tokens are pinned so text and outlines stay dark on white. */
.tt-dash {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
    --bg2: #f7faf9;
    --surface: #ffffff;
    /* shell height: lower the number if your top bar is taller */
    --shell-h: calc(100vh - 215px);
    --v-theme-on-surface: 15, 42, 38;
    --v-theme-on-background: 15, 42, 38;
    --v-theme-surface: 255, 255, 255;
    --v-theme-background: 243, 247, 246;
    --v-theme-primary: 15, 157, 107;
    --v-theme-on-primary: 255, 255, 255;
    --v-border-color: 15, 42, 38;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    background: var(--bg);
    padding-bottom: 24px;
    font-variant-numeric: tabular-nums;
    -webkit-font-smoothing: antialiased;
}
.tt-dash :deep(.v-btn) { letter-spacing: 0; }
.tt-sub { font-size: 12.5px; color: var(--muted); }
.tt-strong { font-weight: 600; color: var(--ink); }
.tt-clip { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ───────── Compact hero (matches Dashboard) ───────── */
.tt-hero {
    position: relative; overflow: hidden; color: #fff; padding: 18px 22px 18px; border-radius: 18px;
    background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%);
    box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95);
}
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.15; letter-spacing: -0.5px; color: #fff; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.22); color: #fff; }
.tt-pill-live { color: #6ee7b7; }
.tt-pulse { width: 7px; height: 7px; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }
.tt-seg { display: inline-flex; padding: 3px; border-radius: 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-seg button { border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: rgba(255, 255, 255, 0.75); padding: 6px 15px; border-radius: 9px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-seg button:hover { color: #fff; }
.tt-seg button.on { background: rgba(255, 255, 255, 0.22); color: #fff; font-weight: 600; }
.tt-seg button:focus-visible { outline: 2px solid #6ee7b7; outline-offset: 2px; }
.tt-btn-ghost { height: 38px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 38px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 13px; color: #fff; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.22; filter: blur(10px); }
.tt-hero-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-txt { flex: 1; min-width: 0; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.3px; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.82); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tt-hero-progress { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: rgba(255, 255, 255, 0.14); }
.tt-hero-progress span { display: block; height: 100%; background: var(--hue); }
.tt-live { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(110, 231, 183, 0.5); white-space: nowrap; }
@media (max-width: 1199px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Card shell ───────── */
.tt-card {
    position: relative; background: var(--surface); border: 1px solid var(--line); border-radius: 16px !important;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35); overflow: hidden;
}
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; z-index: 3; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-shell { height: var(--shell-h); min-height: 560px; container-type: inline-size; container-name: shell; color: var(--ink); }
.tt-split { display: flex; height: 100%; min-height: 0; }
.tt-grid { position: relative; flex: 1; min-width: 0; min-height: 0; display: grid; grid-template-columns: 340px minmax(0, 1fr); }
.tt-grid.has-info { grid-template-columns: 340px minmax(0, 1fr) 300px; }
.tt-pane { min-width: 0; min-height: 0; display: flex; flex-direction: column; background: #fff; }
.tt-list-pane { border-right: 1px solid var(--line); }
.tt-info-pane { border-left: 1px solid var(--line); }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: #e3f6ee; color: #0f9d6b; flex: none; }
.tt-head-ic--lg { width: 42px; height: 42px; border-radius: 12px; }

.tt-chip { display: inline-block; font-size: 10.5px; font-weight: 600; padding: 2px 9px; border-radius: 20px; white-space: nowrap; line-height: 1.5; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-blue { background: #e6edff; color: #1f4e9c; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-red { background: #fce1d8; color: #b4381a; }
.tone-violet { background: #ebe5ff; color: #5b3fd1; }
.tone-teal { background: #d3f1f2; color: #0b6b72; }
.tone-gray { background: #e8ecea; color: #5b6275; }

/* ───────── Channel rail (replaces the wide tab bar) ───────── */
.tt-rail { width: 68px; flex: none; display: flex; flex-direction: column; align-items: center; gap: 9px; padding: 18px 0 14px; background: linear-gradient(180deg, #053d35 0%, #0a5548 100%); overflow-y: auto; overflow-x: hidden; scrollbar-width: none; }
.tt-rail::-webkit-scrollbar { display: none; }
.tt-rail-btn {
    --c: #0f9d6b; position: relative; width: 42px; height: 42px; flex: none; border: 0; border-radius: 13px; display: grid; place-items: center; cursor: pointer; color: #fff;
    background: color-mix(in srgb, var(--c) 30%, rgba(255, 255, 255, 0.06)); transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
}
.tt-rail-btn:hover { background: color-mix(in srgb, var(--c) 55%, rgba(255, 255, 255, 0.06)); transform: translateY(-1px); }
.tt-rail-btn:focus-visible { outline: 2px solid #6ee7b7; outline-offset: 2px; }
.tt-rail-btn.on { background: var(--c); box-shadow: 0 10px 18px -8px var(--c); }
.tt-rail-btn.on::before { content: ""; position: absolute; left: -13px; top: 10px; bottom: 10px; width: 4px; border-radius: 0 4px 4px 0; background: #fff; }
.tt-rail-btn.off { opacity: 0.45; background: transparent; border: 1.5px dashed rgba(255, 255, 255, 0.5); }
.tt-rail-btn--all { background: rgba(255, 255, 255, 0.14); }
.tt-rail-btn--ghost { background: transparent; border: 1.5px dashed rgba(255, 255, 255, 0.45); }
.tt-rail-btn--ghost:hover { background: rgba(255, 255, 255, 0.12); }
.tt-rail-sep { width: 26px; height: 1px; background: rgba(255, 255, 255, 0.2); flex: none; }
.tt-rail-badge { position: absolute; top: -5px; right: -6px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: #ef4444; color: #fff; font-style: normal; font-size: 10.5px; font-weight: 700; display: grid; place-items: center; box-shadow: 0 0 0 2px #0a5548; }

/* ───────── List pane ───────── */
.tt-list-top { display: flex; align-items: center; gap: 10px; padding: 14px 14px 8px; }
.tt-list-title { flex: 1; min-width: 0; display: flex; align-items: center; gap: 10px; }
.tt-list-ic { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; flex: none; }
.tt-list-title b { display: block; font-size: 15px; font-weight: 700; line-height: 1.2; }
.tt-list-title small { display: block; font-size: 11.5px; color: var(--muted); }
.tt-icon-btn { flex: none; border-color: var(--line) !important; color: #3d5450 !important; border-radius: 10px !important; }
.tt-list-search { padding: 0 14px 10px; }
.tt-search { width: 100%; }
.tt-search :deep(.v-field) { border-radius: 10px; background: var(--bg2); font-size: 13px; }
.tt-tabs { display: flex; gap: 2px; margin: 0 14px 10px; padding: 3px; border-radius: 11px; background: #eef3f1; }
.tt-tabs button { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 6px 4px; border: 0; border-radius: 8px; background: transparent; font: inherit; font-size: 12.5px; font-weight: 600; color: var(--muted); cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-tabs button:hover { color: var(--ink); }
.tt-tabs button:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 1px; }
.tt-tabs button.on { background: #fff; color: #0a5548; box-shadow: 0 1px 3px rgba(15, 42, 38, 0.14); }
.tt-tabs span { font-size: 10.5px; font-weight: 700; padding: 0 6px; border-radius: 8px; background: #dfe8e5; color: #3d5450; }
.tt-tabs button.on span { background: #0f9d6b; color: #fff; }

.tt-convo-list { flex: 1; overflow-y: auto; border-top: 1px solid var(--line); }
.tt-convo { display: flex; gap: 11px; padding: 12px 14px; border-bottom: 1px solid #eef3f1; cursor: pointer; border-left: 3px solid transparent; transition: background 0.12s; outline: none; }
.tt-convo:hover { background: #f6faf9; }
.tt-convo:focus-visible { box-shadow: inset 0 0 0 2px #0f9d6b; }
.tt-convo.active { background: color-mix(in srgb, var(--c) 8%, white); border-left-color: var(--c); }
.tt-convo-main { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 3px; }
.tt-convo-name { font-size: 13.5px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-convo.unread .tt-convo-name { font-weight: 700; }
.tt-convo-time { font-size: 11px; color: var(--muted); flex: none; }
.tt-convo-time.late { color: #b4381a; font-weight: 600; }
.tt-convo-preview { flex: 1; min-width: 0; font-size: 12.5px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-convo.unread .tt-convo-preview { color: var(--ink); font-weight: 500; }
.tt-unread { min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: #0f9d6b; color: #fff; font-size: 10.5px; font-weight: 700; display: inline-grid; place-items: center; }
.tt-convo-meta { display: flex; align-items: center; gap: 6px; margin-top: 2px; flex-wrap: nowrap; overflow: hidden; }
.tt-owner { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: #3d5450; white-space: nowrap; }
.tt-owner i { width: 18px; height: 18px; border-radius: 50%; background: #e6edff; color: #1d4ed8; font-style: normal; font-size: 8.5px; font-weight: 700; display: grid; place-items: center; }
.tt-empty-list { padding: 48px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; }
.tt-avatar { position: relative; width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; flex: none; font-size: 13px; font-weight: 700; color: #0f2a26; }
.tt-avatar--md { width: 38px; height: 38px; font-size: 12.5px; }
.tt-avatar--sm { width: 28px; height: 28px; font-size: 10.5px; }
.tt-avatar--lg { width: 68px; height: 68px; margin: 0 auto; font-size: 21px; box-shadow: 0 0 0 4px #fff, 0 10px 20px -10px rgba(15, 42, 38, 0.5); }
.tt-platform-badge { position: absolute; right: -3px; bottom: -3px; width: 17px; height: 17px; border-radius: 50%; border: 2px solid #fff; display: grid; place-items: center; }

/* ───────── Chat header: one row ───────── */
.tt-chat-head { display: flex; align-items: center; flex-wrap: nowrap; gap: 8px; padding: 10px 14px; border-bottom: 1px solid var(--line); background: #fff; }
.tt-chat-title { min-width: 0; flex: 0 1 auto; }
.tt-chat-name { font-size: 14.5px; font-weight: 700; white-space: nowrap; }
.tt-window-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 3px 10px; border-radius: 12px; white-space: nowrap; flex: none; }
.tt-window-chip.ok { color: #0f7a4d; background: #dff5ec; }
.tt-window-chip.closed { color: #b4381a; background: #fce1d8; }
.tt-assign-btn { flex: none; display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 8px 0 5px; border: 1px solid var(--line); border-radius: 17px; background: #fff; font: inherit; font-size: 12.5px; font-weight: 600; color: var(--ink); cursor: pointer; transition: background 0.15s, border-color 0.15s; }
.tt-assign-btn:hover { background: #f6faf9; border-color: #b7e6cd; }
.tt-assign-btn:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-assign-av { width: 24px; height: 24px; border-radius: 50%; background: #e6edff; color: #1d4ed8; font-size: 9.5px; font-weight: 700; display: grid; place-items: center; }
.tt-assign-btn.empty .tt-assign-av { background: #fdecc8; color: #8a5a00; }
.tt-resolve-btn { flex: none; color: #fff; font-weight: 600; border-radius: 10px !important; }
.tt-chat-head .tt-resolve-btn.v-btn--variant-outlined { color: var(--ink); }
.tt-back-btn { display: none !important; }
.tt-context { display: flex; align-items: center; gap: 6px; padding: 7px 16px; font-size: 12.5px; font-weight: 600; background: #fff8e8; border-bottom: 1px solid #f3e3b8; color: #8a5a00; }
.tt-context--blue { background: #eaf1ff; border-bottom-color: #cfe0ff; color: #1d4ed8; }

/* ───────── Thread (tighter so more messages fit) ───────── */
.tt-thread-wrap { position: relative; flex: 1; min-height: 0; background-color: #f0f5f3; background-image: radial-gradient(#dbe6e2 1px, transparent 1px); background-size: 18px 18px; }
.tt-thread { position: absolute; inset: 0; overflow-y: auto; padding: 12px 20px 14px; display: flex; flex-direction: column; gap: 2px; }
.tt-day { align-self: center; margin: 8px 0 4px; }
.tt-day span { font-size: 11.5px; font-weight: 600; color: #4d6561; background: #fff; border: 1px solid var(--line); padding: 2px 13px; border-radius: 12px; }
.tt-msg-system { align-self: center; font-size: 11.5px; color: #4d6561; background: rgba(255, 255, 255, 0.85); padding: 2px 12px; border-radius: 12px; margin: 6px 0; }
.tt-row { display: flex; align-items: flex-end; gap: 8px; max-width: 76%; }
.tt-row.first { margin-top: 8px; }
.tt-row.customer { align-self: flex-start; }
.tt-row.agent, .tt-row.note { align-self: flex-end; flex-direction: row-reverse; }
.tt-row-av { width: 28px; flex: none; }
.tt-row-body { min-width: 0; display: flex; flex-direction: column; }
.tt-row.agent .tt-row-body, .tt-row.note .tt-row-body { align-items: flex-end; }
.tt-row-name { font-size: 11px; font-weight: 600; color: #5c726e; margin: 0 6px 2px; }
.tt-bubble { padding: 7px 13px; font-size: 13.5px; line-height: 1.5; box-shadow: 0 1px 1px rgba(15, 42, 38, 0.06); }
.tt-row.customer .tt-bubble { background: #fff; color: var(--ink); border-radius: 6px 18px 18px 6px; }
.tt-row.customer.first .tt-bubble { border-top-left-radius: 18px; }
.tt-row.customer.last .tt-bubble { border-bottom-left-radius: 18px; }
.tt-row.agent .tt-bubble { background: linear-gradient(135deg, #0b6150, #0a5548); color: #fff; border-radius: 18px 6px 6px 18px; }
.tt-row.agent.first .tt-bubble { border-top-right-radius: 18px; }
.tt-row.agent.last .tt-bubble { border-bottom-right-radius: 18px; }
.tt-row.note .tt-bubble { background: #fff6dc; color: #5c4300; border: 1px dashed #e0b94d; border-radius: 14px; }
.tt-msg-text { white-space: pre-wrap; overflow-wrap: anywhere; }
.tt-bubble-tag { display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; opacity: 0.85; margin-bottom: 3px; }
.tt-msg-meta { display: flex; align-items: center; font-size: 11px; color: #5c726e; margin: 2px 6px 0; }
.tt-retry { margin-left: 6px; color: #dc2626; font-weight: 600; cursor: pointer; text-decoration: underline; }
.tt-attach-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.tt-attach-img { max-width: 240px; max-height: 180px; border-radius: 10px; object-fit: cover; }
.tt-attach-file { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; background: rgba(0, 0, 0, 0.08); padding: 4px 8px; border-radius: 6px; }
.tt-prod { display: flex; gap: 10px; background: #fff; color: var(--ink); border-radius: 12px; padding: 8px; margin-bottom: 6px; min-width: 240px; }
.tt-prod-img { width: 48px; height: 48px; border-radius: 10px; background: #e3f6ee; display: grid; place-items: center; flex: none; }
.tt-prod-info { display: flex; flex-direction: column; justify-content: center; min-width: 0; line-height: 1.35; }
.tt-prod-info b { font-size: 13px; }
.tt-prod-info span { font-size: 13px; font-weight: 700; color: #0f9d6b; }
.tt-prod-info small.in { color: #0f7a4d; font-size: 11px; font-weight: 600; }
.tt-prod-info small.out { color: #b4381a; font-size: 11px; font-weight: 600; }
.tt-scroll-btn { position: absolute; right: 20px; bottom: 12px; width: 34px; height: 34px; border-radius: 50%; border: 1px solid var(--line); background: #fff; box-shadow: 0 4px 14px rgba(15, 42, 38, 0.18); display: grid; place-items: center; cursor: pointer; color: var(--ink); }
.tt-empty-thread { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 4px; background: #f7faf9; padding: 24px; }
.tt-empty-ic { width: 76px; height: 76px; border-radius: 50%; background: #e3f6ee; display: grid; place-items: center; margin-bottom: 10px; }
.tt-empty-ic--sm { width: 56px; height: 56px; margin-bottom: 0; }
.tt-empty-title { font-size: 17px; font-weight: 700; color: var(--ink); }
.tt-empty-chans { display: flex; gap: 6px; margin-top: 16px; }
.tt-empty-chans span { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; }

/* ───────── Composer (slim: suggestions row + one box with a footer toolbar) ───────── */
.tt-composer-wrap { position: relative; padding: 0 14px 12px; background: #fff; border-top: 1px solid var(--line); }
.tt-suggest { display: flex; align-items: center; gap: 6px; padding: 8px 0 8px; overflow-x: auto; scrollbar-width: none; }
.tt-suggest::-webkit-scrollbar { display: none; }
.tt-suggest button { flex: none; font: inherit; font-size: 12px; font-weight: 600; color: #0a5548; background: #eaf8f1; border: 1px solid #c6ebd9; border-radius: 14px; padding: 2px 12px; cursor: pointer; white-space: nowrap; }
.tt-suggest button:hover { background: #d9f2e6; }
.tt-ai-chip { flex: none; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 2px 12px; border-radius: 14px; cursor: pointer; color: #5b3fd1; background: #ebe5ff; border: 1px solid #d6caff; white-space: nowrap; }
.tt-ai-chip:hover { background: #e0d6ff; }
.tt-composer { margin-top: 10px; border: 1.5px solid var(--line); border-radius: 14px; padding: 4px 8px 6px 12px; background: #fff; transition: border-color 0.15s, box-shadow 0.15s; }
.tt-suggest + .tt-composer { margin-top: 0; }
.tt-composer:focus-within { border-color: #0f9d6b; box-shadow: 0 0 0 3px rgba(15, 157, 107, 0.12); }
.tt-composer--note { background: #fffaf0; border-color: #ecd391; }
.tt-composer--note:focus-within { border-color: #d97706; box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.14); }
.tt-composer.drag { border-color: #0f9d6b; border-style: dashed; background: #f0faf5; }
.tt-textarea :deep(textarea) { color: var(--ink); font-size: 13.5px; line-height: 1.5; }
.tt-textarea :deep(.v-field__input) { padding: 8px 2px 4px; min-height: 0; }
.tt-composer-banner { display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; line-height: 1.5; color: #7a4f00; background: #fff8e8; border: 1px solid #f3e3b8; border-radius: 8px; padding: 8px 12px; margin: 4px 4px 4px 0; }
.tt-tpl-preview { margin: 8px 4px 4px 0; font-size: 12.5px; line-height: 1.5; color: #3d5450; background: #f0faf5; border: 1px dashed #b7e6cd; border-radius: 8px; padding: 8px 12px; }
.tt-toolbar { display: flex; align-items: center; gap: 2px; padding-top: 2px; }
.tt-mode { display: inline-flex; padding: 2px; border-radius: 10px; background: #eef3f1; margin-right: 2px; }
.tt-mode button { display: inline-flex; align-items: center; gap: 4px; font: inherit; font-size: 12px; font-weight: 600; color: var(--muted); padding: 3px 10px; border-radius: 8px; border: 0; background: transparent; cursor: pointer; }
.tt-mode button.on { background: #fff; color: #0a5548; box-shadow: 0 1px 3px rgba(15, 42, 38, 0.14); }
.tt-composer--note .tt-mode button.on { color: #8a5a00; }
.tt-tool-sep { width: 1px; height: 18px; background: var(--line); margin: 0 4px; }
.tt-seg-mini { display: inline-flex; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; margin-left: 4px; }
.tt-seg-mini button { font: inherit; font-size: 11.5px; font-weight: 600; border: 0; background: #fff; color: var(--muted); padding: 3px 10px; cursor: pointer; }
.tt-seg-mini button.on { background: #1877f2; color: #fff; }
.tt-tool { color: #4d6561 !important; }
.tt-tool-ai { color: #5b3fd1 !important; }
.tt-send-btn { color: #fff !important; font-weight: 600; border-radius: 10px !important; }
.tt-thumbs { display: flex; flex-wrap: wrap; gap: 8px; padding: 6px 0; }
.tt-thumb { position: relative; width: 60px; height: 60px; border-radius: 10px; overflow: hidden; border: 1px solid var(--line); background: #f3f7f6; display: grid; place-items: center; }
.tt-thumb img { width: 100%; height: 100%; object-fit: cover; }
.tt-thumb span { display: flex; flex-direction: column; align-items: center; padding: 4px; }
.tt-thumb small { font-size: 9px; max-width: 54px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-thumb button { position: absolute; top: 3px; right: 3px; width: 17px; height: 17px; border-radius: 50%; border: 0; background: rgba(15, 42, 38, 0.75); display: grid; place-items: center; cursor: pointer; }
.tt-slash { position: absolute; left: 14px; right: 14px; bottom: calc(100% - 4px); background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 8px 24px rgba(15, 42, 38, 0.16); z-index: 6; overflow: hidden; }
.tt-slash-item { display: flex; gap: 10px; padding: 9px 14px; cursor: pointer; font-size: 12.5px; }
.tt-slash-item:hover { background: #eaf8f1; }
.tt-slash-item b { color: #0f9d6b; flex: none; }
.tt-slash-item span { color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ───────── Customer panel ───────── */
.tt-info-head { display: flex; align-items: center; padding: 12px 16px; border-bottom: 1px solid var(--line); }
.tt-info-title { font-size: 14px; font-weight: 600; }
.tt-info-scroll { flex: 1; overflow-y: auto; padding: 0 16px 18px; }
.tt-profile { display: flex; flex-direction: column; align-items: center; text-align: center; margin: 0 -16px; padding: 20px 16px 16px; background: linear-gradient(180deg, #e3f6ee 0%, #fff 100%); }
.tt-info-name { font-size: 15.5px; font-weight: 700; margin-top: 10px; }
.tt-quick-actions { display: flex; justify-content: center; gap: 8px; margin-top: 12px; }
.tt-quick-actions a { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--line); background: #fff; display: grid; place-items: center; color: #3d5450; text-decoration: none; }
.tt-quick-actions a:hover { background: #eaf8f1; border-color: #0f9d6b; color: #0f9d6b; }
.tt-info-tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px; }
.tt-info-tile { --c: #0f9d6b; border-radius: 12px; padding: 9px 11px; background: var(--bg2); border: 1px solid var(--line); border-bottom: 3px solid var(--c); }
.tt-tile-value { font-size: 16px; font-weight: 700; color: var(--c); }
.tt-ai-card { margin-top: 12px; padding: 11px 12px; border-radius: 12px; background: #f4f0ff; border: 1px solid #e2d9ff; }
.tt-ai-card p { margin: 6px 0 0; font-size: 12.5px; line-height: 1.55; color: var(--ink); }
.tt-ai-tag { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 20px; background: #ebe5ff; color: #5b3fd1; }
.tt-info-rows { display: flex; flex-direction: column; gap: 8px; margin: 14px 0 4px; font-size: 12.5px; }
.tt-info-rows .v-icon { margin-right: 8px; color: #6b7f7b; }
.tt-field-label { font-size: 12px; font-weight: 600; color: var(--ink); margin: 18px 0 8px; padding-top: 14px; border-top: 1px dashed #d5dfdc; }
.tt-order-row { display: flex; align-items: center; padding: 7px 0; border-bottom: 1px solid #eef3f1; font-size: 12.5px; }
.tt-tagchip { display: inline-flex; align-items: center; gap: 3px; font-size: 11.5px; font-weight: 600; color: #0a5548; background: #e3f6ee; border-radius: 20px; padding: 2px 6px 2px 10px; }
.tt-tagchip button { border: 0; background: transparent; cursor: pointer; display: grid; place-items: center; color: #0a5548; }

/* ───────── Dialog ───────── */
.tt-dialog { position: relative; background: #fff; color: var(--ink); border-radius: 16px; overflow: hidden; font-family: Poppins, "Segoe UI", sans-serif; }
.tt-dialog::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-dialog-head { display: flex; align-items: center; padding: 20px 20px 14px; border-bottom: 1px solid var(--line); }
.tt-dialog-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 16px 20px; }
.tt-ch-card { display: flex; align-items: center; gap: 12px; border: 1px solid var(--line); border-radius: 12px; padding: 12px; background: #f7faf9; }
.tt-ch-card.live { border-color: #b7e6cd; background: #f6fcf9; }
.tt-ch-ic { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; flex: none; }
.tt-ch-main { min-width: 0; flex: 1; display: flex; flex-direction: column; line-height: 1.35; }
.tt-ch-main b { font-size: 13px; }
.tt-ch-main span { font-size: 11.5px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-ch-btn { flex: none; color: var(--ink); border-radius: 10px !important; }
.tt-ch-btn.v-btn--variant-flat { color: #fff; }
.tt-dialog-foot { padding: 0 20px 18px; line-height: 1.6; }
@media (max-width: 599px) { .tt-dialog-grid { grid-template-columns: 1fr; } }

/* ───────── Responsive (container-based so the app sidebar is accounted for) ───────── */
@container shell (max-width: 1280px) {
    .tt-assign-name { display: none; }
}
@container shell (max-width: 1180px) {
    .tt-grid.has-info { grid-template-columns: 330px minmax(0, 1fr); }
    .tt-info-pane { position: absolute; top: 0; right: 0; bottom: 0; width: 310px; z-index: 5; box-shadow: -10px 0 28px rgba(15, 42, 38, 0.16); }
}
@container shell (max-width: 980px) {
    .tt-grid, .tt-grid.has-info { grid-template-columns: 310px minmax(0, 1fr); }
}
@container shell (max-width: 760px) {
    .tt-split { flex-direction: column; }
    .tt-rail { width: auto; height: 58px; flex-direction: row; padding: 8px 14px; overflow-x: auto; overflow-y: hidden; }
    .tt-rail-btn.on::before { left: 10px; right: 10px; top: auto; bottom: -9px; width: auto; height: 4px; border-radius: 4px 4px 0 0; }
    .tt-rail-sep { width: 1px; height: 26px; }
    .tt-grid, .tt-grid.has-info { grid-template-columns: minmax(0, 1fr); flex: 1; }
    .tt-chat-pane { display: none; }
    .tt-grid.show-thread .tt-list-pane { display: none; }
    .tt-grid.show-thread .tt-chat-pane { display: flex; }
    .tt-info-pane { width: 100%; }
    .tt-back-btn { display: inline-flex !important; }
    .tt-row { max-width: 90%; }
    .tt-window-chip, .tt-tool-ai { display: none; }
}
@media (prefers-reduced-motion: reduce) { .tt-rail-btn, .tt-convo, .tt-composer, .tt-tabs button { transition: none; } .tt-rail-btn:hover { transform: none; } }
</style>

<!-- Overlays (menus, dialogs) are teleported outside the page, so they need their own pinned colours -->
<style>
.tt-overlay {
    --v-theme-on-surface: 15, 42, 38;
    --v-theme-surface: 255, 255, 255;
    --v-theme-primary: 15, 157, 107;
    --v-border-color: 15, 42, 38;
    color: #0f2a26;
    font-family: Poppins, "Segoe UI", sans-serif;
}
.tt-overlay .v-list { background: #fff; color: #0f2a26; border-radius: 12px; }
.tt-overlay .v-list-item--active { color: #0a5548; background: #eaf8f1; }
.tt-emoji-pop { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; padding: 8px; background: #fff; border-radius: 12px; width: 270px; }
.tt-emoji-pop button { font-size: 19px; border: 0; background: transparent; border-radius: 6px; padding: 3px; cursor: pointer; }
.tt-emoji-pop button:hover { background: #eaf8f1; }
.tt-prod-pop { width: 330px; background: #fff; border-radius: 12px; padding: 10px; }
.tt-prod-pop-list { max-height: 250px; overflow-y: auto; margin-top: 8px; }
.tt-prod-pop-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 10px; border-radius: 8px; cursor: pointer; }
.tt-prod-pop-item:hover { background: #eaf8f1; }
.tt-prod-pop-item b { display: block; font-size: 13px; font-weight: 600; }
.tt-prod-pop-item span { font-size: 12px; color: #6b7f7b; }
.tt-prod-pop small.in { color: #0f7a4d; font-size: 11px; font-weight: 600; }
.tt-prod-pop small.out { color: #b4381a; font-size: 11px; font-weight: 600; }
</style>