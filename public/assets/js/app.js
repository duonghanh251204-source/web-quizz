(() => {
    const insertAtCursor = (textarea, text) => {
        if (!(textarea instanceof HTMLTextAreaElement)) return;

        const start = textarea.selectionStart ?? 0;
        const end = textarea.selectionEnd ?? 0;
        const value = textarea.value;
        textarea.value = `${value.slice(0, start)}${text}${value.slice(end)}`;
        const next = start + text.length;
        textarea.selectionStart = textarea.selectionEnd = next;
    };

    /** Khớp server QuizRichContent::MAX_DATA_URI_CHARS (để lưu được sau khi nén) */
    const MAX_PASTE_DATA_URL_CHARS = 2400000;

    const allowedImageMimes = new Set(["png", "jpeg", "jpg", "gif", "webp"]);

    const mimeFromDataUrl = (dataUrl) => {
        if (typeof dataUrl !== "string") return "image/png";
        const m = dataUrl.match(/^data:(image\/[a-z0-9.+-]+);base64,/i);
        return m && m[1] ? m[1].toLowerCase() : "image/png";
    };

    const tryExtractMarkdownDataImage = (text) => {
        if (typeof text !== "string" || text.length === 0) return null;
        const start = text.indexOf("![");
        if (start === -1) return null;
        const rb = text.indexOf("]", start + 2);
        if (rb === -1 || text[rb + 1] !== "(") return null;
        let uriStart = rb + 2;
        while (uriStart < text.length && /\s/.test(text[uriStart])) uriStart += 1;
        if (text.slice(uriStart, uriStart + 11).toLowerCase() !== "data:image/") return null;
        const uriEnd = text.indexOf(")", uriStart);
        if (uriEnd === -1) return null;
        return text.slice(start, uriEnd + 1);
    };

    const tryExtractRawDataImageUrl = (text) => {
        if (typeof text !== "string") return null;
        const marker = "data:image/";
        let i = 0;
        while (i < text.length) {
            const start = text.indexOf(marker, i);
            if (start === -1) return null;
            const afterMime = text.indexOf(";base64,", start + marker.length);
            if (afterMime === -1) {
                i = start + 1;
                continue;
            }
            const mime = text.slice(start + marker.length, afterMime).toLowerCase();
            if (!allowedImageMimes.has(mime)) {
                i = start + 1;
                continue;
            }
            const payloadStart = afterMime + ";base64,".length;
            let end = payloadStart;
            while (end < text.length) {
                const ch = text[end];
                if (
                    (ch >= "A" && ch <= "Z")
                    || (ch >= "a" && ch <= "z")
                    || (ch >= "0" && ch <= "9")
                    || ch === "+" || ch === "/" || ch === "=" || ch === "-" || ch === "_"
                ) {
                    end += 1;
                    continue;
                }
                if (ch === " " || ch === "\r" || ch === "\n" || ch === "\t") {
                    end += 1;
                    continue;
                }
                break;
            }
            const raw = text.slice(start, end).replace(/\s+/g, "");
            if (raw.length > 40) return raw;
            i = start + 1;
        }
        return null;
    };

    const tryExtractImgDataUrlFromHtml = (html) => {
        if (typeof html !== "string" || html.length === 0) return null;
        const lower = html.toLowerCase();
        let p = 0;
        while (p < html.length) {
            const img = lower.indexOf("<img", p);
            if (img === -1) return null;
            const srcKey = lower.indexOf("src=", img);
            if (srcKey === -1 || srcKey > img + 200) {
                p = img + 4;
                continue;
            }
            let q = srcKey + 4;
            while (q < html.length && /\s/.test(html[q])) q += 1;
            const quote = html[q];
            if (quote !== '"' && quote !== "'") {
                p = img + 4;
                continue;
            }
            const urlStart = q + 1;
            const urlEnd = html.indexOf(quote, urlStart);
            if (urlEnd === -1) return null;
            const url = html.slice(urlStart, urlEnd);
            if (url.slice(0, 11).toLowerCase() === "data:image/" && url.includes(";base64,")) {
                return url.replace(/\s+/g, "");
            }
            p = img + 4;
        }
        return null;
    };

    const downscaleDataUrl = (dataUrl, mimeType, maxDim, jpegQuality, done) => {
        if (typeof dataUrl !== "string" || !dataUrl.startsWith("data:image/")) {
            done(dataUrl);
            return;
        }

        const img = new Image();
        img.onload = () => {
            try {
                let w = img.naturalWidth || img.width;
                let h = img.naturalHeight || img.height;
                if (!w || !h) {
                    done(dataUrl);
                    return;
                }

                if (w > maxDim || h > maxDim) {
                    if (w >= h) {
                        h = Math.max(1, Math.round((h * maxDim) / w));
                        w = maxDim;
                    } else {
                        w = Math.max(1, Math.round((w * maxDim) / h));
                        h = maxDim;
                    }
                }

                const canvas = document.createElement("canvas");
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext("2d");
                if (!ctx) {
                    done(dataUrl);
                    return;
                }

                ctx.drawImage(img, 0, 0, w, h);
                const useJpeg = mimeType !== "image/png" && mimeType !== "image/webp";
                const outMime = useJpeg ? "image/jpeg" : mimeType || "image/png";
                const quality = outMime === "image/jpeg" ? jpegQuality : undefined;
                let out = canvas.toDataURL(outMime, quality);
                if (typeof out !== "string" || out.length === 0) {
                    out = dataUrl;
                }
                if (out.length > dataUrl.length && maxDim >= 800) {
                    out = dataUrl;
                }
                done(out);
            } catch {
                done(dataUrl);
            }
        };
        img.onerror = () => done(dataUrl);
        img.src = dataUrl;
    };

    const insertComposedAtSelection = (textarea, composed) => {
        if (!(textarea instanceof HTMLTextAreaElement)) return;
        const start = textarea.selectionStart ?? 0;
        const end = textarea.selectionEnd ?? 0;
        const value = textarea.value;
        textarea.value = `${value.slice(0, start)}${composed}${value.slice(end)}`;
        const next = start + composed.length;
        textarea.selectionStart = textarea.selectionEnd = next;
    };

    const insertPastedImage = (target, dataUrl, fileMime, prefix = "", suffix = "") => {
        const mime = fileMime && fileMime.startsWith("image/") ? fileMime : mimeFromDataUrl(dataUrl);

        const tryChain = (url, useMime, dim, quality) => {
            downscaleDataUrl(url, useMime, dim, quality, (out) => {
                if (out.length <= MAX_PASTE_DATA_URL_CHARS) {
                    insertComposedAtSelection(target, `${prefix}![](${out})${suffix}`);
                    target.dispatchEvent(new Event("input", { bubbles: true }));
                    return;
                }
                if (dim <= 280) {
                    window.alert(
                        "Ảnh vẫn quá lớn sau khi nén. Hãy dùng nút « Chèn ảnh », chụp vùng nhỏ hơn, hoặc giảm độ phân giải. Kiểm tra php.ini: post_max_size ≥ 12M."
                    );
                    return;
                }
                const nextDim = Math.max(280, Math.round(dim * 0.62));
                const nextQ = quality <= 0.58 ? 0.52 : Math.max(0.52, quality * 0.88);
                tryChain(out, "image/jpeg", nextDim, nextQ);
            });
        };

        tryChain(dataUrl, mime, 1200, 0.74);
    };

    document.addEventListener(
        "paste",
        (event) => {
            const target = event.target;
            if (!(target instanceof HTMLTextAreaElement)) return;
            if (target.getAttribute("data-rich-paste") !== "1") return;

            const cd = event.clipboardData;
            if (!cd) return;

            const items = cd.items;
            if (items) {
                for (let i = 0; i < items.length; i += 1) {
                    const item = items[i];
                    if (item.kind !== "file") continue;
                    if (!item.type.startsWith("image/")) continue;

                    event.preventDefault();
                    const file = item.getAsFile();
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = () => {
                        const raw = typeof reader.result === "string" ? reader.result : "";
                        if (!raw.startsWith("data:image/")) return;
                        insertPastedImage(target, raw, file.type);
                    };
                    reader.readAsDataURL(file);
                    return;
                }
            }

            const html = cd.getData("text/html");
            if (html && /data:image\//i.test(html)) {
                const fromHtml = tryExtractImgDataUrlFromHtml(html);
                if (fromHtml) {
                    event.preventDefault();
                    insertPastedImage(target, fromHtml, mimeFromDataUrl(fromHtml));
                    return;
                }
            }

            const plain = cd.getData("text/plain");
            if (plain && plain.includes("data:image/")) {
                const md = tryExtractMarkdownDataImage(plain);
                if (md) {
                    event.preventDefault();
                    const mdStart = plain.indexOf("![");
                    const mdEnd = mdStart >= 0 ? mdStart + md.length : -1;
                    const prefix = mdStart > 0 ? plain.slice(0, mdStart) : "";
                    const suffix = mdEnd > 0 ? plain.slice(mdEnd) : "";
                    const rb = md.indexOf("]");
                    let uriStart = rb !== -1 && md[rb + 1] === "(" ? rb + 2 : -1;
                    if (uriStart > 0) {
                        while (uriStart < md.length && /\s/.test(md[uriStart])) uriStart += 1;
                    }
                    const uriEnd = uriStart > 0 ? md.indexOf(")", uriStart) : -1;
                    const url =
                        uriStart > 0 && uriEnd > uriStart
                            ? md.slice(uriStart, uriEnd).replace(/\s+/g, "")
                            : "";
                    if (url.startsWith("data:image/")) {
                        insertPastedImage(target, url, mimeFromDataUrl(url), prefix, suffix);
                    } else {
                        insertAtCursor(target, md);
                        target.dispatchEvent(new Event("input", { bubbles: true }));
                    }
                    return;
                }
                const rawUrl = tryExtractRawDataImageUrl(plain);
                if (rawUrl) {
                    event.preventDefault();
                    const rs = plain.indexOf(rawUrl);
                    const pfx = rs > 0 ? plain.slice(0, rs) : "";
                    const sfx = rs >= 0 ? plain.slice(rs + rawUrl.length) : "";
                    insertPastedImage(target, rawUrl, mimeFromDataUrl(rawUrl), pfx, sfx);
                    return;
                }
            }
        },
        true
    );

    const MAX_PREVIEW_DATA_URI = 2500000;

    const escapeHtmlPreview = (s) =>
        s
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");

    const attrEscapeUri = (uri) => escapeHtmlPreview(uri).replace(/\n/g, "");

    const isSafeDataUriForPreview = (uri) => {
        if (typeof uri !== "string" || uri.length === 0 || uri.length > MAX_PREVIEW_DATA_URI) return false;
        if (!uri.toLowerCase().startsWith("data:image/")) return false;
        const semi = uri.indexOf(";base64,");
        if (semi === -1) return false;
        const mime = uri.slice("data:image/".length, semi).toLowerCase();
        if (!allowedImageMimes.has(mime)) return false;
        return true;
    };

    const isSafeHttpImageUrlForPreview = (uri) => {
        if (typeof uri !== "string" || uri.length === 0) return false;
        return /^(\/|https?:\/\/)[^\s<>"']+\.(png|jpe?g|gif|webp)(\?[^\s<>"']*)?$/i.test(uri.trim());
    };

    const renderQuestionImagePreview = (textarea) => {
        if (!(textarea instanceof HTMLTextAreaElement)) return;
        if (textarea.getAttribute("data-question-live-preview") !== "1") return;
        const host = textarea.closest("[data-rich-field]");
        const body = host?.querySelector("[data-question-img-preview-body]");
        const wrap = host?.querySelector("[data-question-img-preview]");
        if (!(body instanceof HTMLElement) || !(wrap instanceof HTMLElement)) return;

        const content = textarea.value;
        let html = "";
        let i = 0;
        const len = content.length;

        while (i < len) {
            const pos = content.indexOf("![", i);
            if (pos === -1) {
                html += escapeHtmlPreview(content.slice(i)).replace(/\n/g, "<br>");
                break;
            }
            html += escapeHtmlPreview(content.slice(i, pos)).replace(/\n/g, "<br>");
            const altStart = pos + 2;
            const altEnd = content.indexOf("]", altStart);
            if (altEnd === -1 || content[altEnd + 1] !== "(") {
                html += escapeHtmlPreview(content.slice(pos, pos + 2)).replace(/\n/g, "<br>");
                i = pos + 2;
                continue;
            }
            let uriStart = altEnd + 2;
            while (uriStart < len && /\s/.test(content[uriStart])) uriStart += 1;
            const uriEnd = content.indexOf(")", uriStart);
            if (uriEnd === -1) {
                html += escapeHtmlPreview(content.slice(pos)).replace(/\n/g, "<br>");
                break;
            }
            const uri = content.slice(uriStart, uriEnd).replace(/\s+/g, "");
            const alt = content.slice(altStart, altEnd);
            if (isSafeDataUriForPreview(uri) || isSafeHttpImageUrlForPreview(uri)) {
                html += `<img class="img-fluid quiz-inline-img" src="${attrEscapeUri(uri)}" alt="${escapeHtmlPreview(alt)}" loading="lazy">`;
            } else {
                html += escapeHtmlPreview(content.slice(pos, uriEnd + 1)).replace(/\n/g, "<br>");
            }
            i = uriEnd + 1;
        }

        html = html.replace(
            /&lt;img\b[^&]*?src=&quot;([^&]+)&quot;[^&]*?&gt;/gi,
            (full, src) => {
                const decodedSrc = src.replace(/&amp;/g, "&");
                if (!isSafeDataUriForPreview(decodedSrc) && !isSafeHttpImageUrlForPreview(decodedSrc)) {
                    return full;
                }
                return `<img class="img-fluid quiz-inline-img" src="${attrEscapeUri(decodedSrc)}" loading="lazy">`;
            }
        );

        body.innerHTML = html;
        const hasImg = body.querySelector("img") !== null;
        wrap.hidden = !hasImg;
    };

    const refreshAllQuestionPreviews = () => {
        document.querySelectorAll('textarea[data-question-live-preview="1"]').forEach((ta) => {
            if (ta instanceof HTMLTextAreaElement) renderQuestionImagePreview(ta);
        });
    };

    let previewDebounce = null;
    document.addEventListener(
        "input",
        (e) => {
            const t = e.target;
            if (!(t instanceof HTMLTextAreaElement)) return;
            if (t.getAttribute("data-question-live-preview") !== "1") return;
            window.clearTimeout(previewDebounce);
            previewDebounce = window.setTimeout(() => renderQuestionImagePreview(t), 120);
        },
        true
    );

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", refreshAllQuestionPreviews);
    } else {
        refreshAllQuestionPreviews();
    }

    document.addEventListener("click", (event) => {
        const raw = event.target;
        if (!(raw instanceof Element)) return;
        const btn = raw.closest("[data-rich-image-pick]");
        if (!(btn instanceof HTMLButtonElement)) return;
        const wrap = btn.closest("[data-rich-field]");
        if (!(wrap instanceof HTMLElement)) return;
        const input = wrap.querySelector("[data-rich-image-input]");
        if (input instanceof HTMLInputElement) input.click();
    });

    document.addEventListener("change", (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) return;
        if (input.getAttribute("data-rich-image-input") !== "1") return;
        const wrap = input.closest("[data-rich-field]");
        if (!(wrap instanceof HTMLElement)) return;
        const ta = wrap.querySelector("textarea[data-rich-paste='1']");
        if (!(ta instanceof HTMLTextAreaElement)) return;
        const file = input.files && input.files.length > 0 ? input.files[0] : null;
        input.value = "";
        if (!file || !file.type.startsWith("image/")) return;
        const reader = new FileReader();
        reader.onload = () => {
            const raw = typeof reader.result === "string" ? reader.result : "";
            if (!raw.startsWith("data:image/")) return;
            insertPastedImage(ta, raw, file.type);
        };
        reader.readAsDataURL(file);
    });

    document.addEventListener("dragover", (event) => {
        const t = event.target;
        if (!(t instanceof Element)) return;
        const wrap = t.closest("[data-rich-field]");
        if (!wrap) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = "copy";
    });

    document.addEventListener("drop", (event) => {
        const t = event.target;
        if (!(t instanceof Element)) return;
        const wrap = t.closest("[data-rich-field]");
        if (!(wrap instanceof HTMLElement)) return;
        const ta = wrap.querySelector("textarea[data-rich-paste='1']");
        if (!(ta instanceof HTMLTextAreaElement)) return;
        const file = event.dataTransfer?.files?.[0];
        if (!file || !file.type.startsWith("image/")) return;
        event.preventDefault();
        const reader = new FileReader();
        reader.onload = () => {
            const raw = typeof reader.result === "string" ? reader.result : "";
            if (!raw.startsWith("data:image/")) return;
            insertPastedImage(ta, raw, file.type);
        };
        reader.readAsDataURL(file);
    });

    const setupFormSubmitLock = () => {
        document.querySelectorAll("form").forEach((form) => {
            if (
                form.dataset.noSubmitLock === "1" ||
                form.hasAttribute("data-no-submit-lock") ||
                form.hasAttribute("data-delete-confirm")
            ) {
                return;
            }

            form.addEventListener("submit", () => {
                const submit = form.querySelector('button[type="submit"]');
                if (!(submit instanceof HTMLButtonElement)) return;
                if (submit.dataset.locked === "1") return;

                submit.dataset.locked = "1";
                submit.disabled = true;
                submit.dataset.original = submit.textContent || "Gửi";
                submit.textContent = "Đang xử lý...";
            });
        });
    };

    const setupAiQuizGenerationLoading = () => {
        const form = document.querySelector("[data-ai-quiz-form]");
        const overlay = document.querySelector("[data-ai-gen-loading]");
        const submitBtn = document.querySelector("[data-ai-quiz-submit]");
        const title = document.getElementById("ai-gen-loading-title");

        if (!(form instanceof HTMLFormElement)) return;
        if (!(overlay instanceof HTMLElement)) return;

        form.addEventListener("submit", () => {
            if (!(submitBtn instanceof HTMLButtonElement)) return;

            overlay.hidden = false;
            overlay.setAttribute("aria-hidden", "false");
            overlay.classList.add("is-visible");
            document.body.classList.add("ai-gen-loading-active");
            submitBtn.disabled = true;
            submitBtn.dataset.locked = "1";
            submitBtn.textContent = "Đang tạo đề…";

            requestAnimationFrame(() => {
                if (title instanceof HTMLElement) {
                    title.focus({ preventScroll: true });
                }
            });
        });
    };

    const setupPasswordToggles = () => {
        document.querySelectorAll("[data-password-toggle]").forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) return;

            const selector = button.dataset.passwordToggle;
            if (!selector) return;

            const input = document.querySelector(selector);
            if (!(input instanceof HTMLInputElement)) return;

            button.addEventListener("click", () => {
                const isPassword = input.type === "password";
                input.type = isPassword ? "text" : "password";
                button.textContent = isPassword ? "Ẩn" : "Hiện";
            });
        });
    };

    const setupFileInputFeedback = () => {
        const fileInput = document.querySelector("[data-upload-input]");
        const fileLabel = document.querySelector("[data-upload-name]");

        if (!(fileInput instanceof HTMLInputElement)) return;
        if (!(fileLabel instanceof HTMLElement)) return;

        fileInput.addEventListener("change", () => {
            const file = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0] : null;
            fileLabel.textContent = file ? `${file.name} (${Math.ceil(file.size / 1024)} KB)` : "Chưa chọn tệp.";
        });
    };

    const setupTableExpandRows = () => {
        document.querySelectorAll("[data-expand-target]").forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) return;

            const targetId = button.dataset.expandTarget;
            if (!targetId) return;

            const target = document.getElementById(targetId);
            if (!(target instanceof HTMLTableRowElement)) return;

            button.addEventListener("click", () => {
                const expanded = button.getAttribute("aria-expanded") === "true";
                button.setAttribute("aria-expanded", expanded ? "false" : "true");
                target.hidden = expanded;
                button.textContent = expanded ? "Chi tiết" : "Thu gọn";
            });
        });
    };

    const setupExamExperience = () => {
        const examRoot = document.querySelector("[data-exam-root]");
        if (!(examRoot instanceof HTMLElement)) return;

        const form = examRoot.querySelector("form");
        if (!(form instanceof HTMLFormElement)) return;

        const progressLabel = examRoot.querySelector("[data-progress-label]");
        const progressFill = examRoot.querySelector("[data-progress-fill]");
        const questionBlocks = Array.from(examRoot.querySelectorAll("[data-question-id]"));
        const navButtons = Array.from(examRoot.querySelectorAll("[data-nav-question]"));
        const timerEl = examRoot.querySelector("[data-exam-timer]");

        const totalQuestions = questionBlocks.length;
        let submitted = false;

        const getAnsweredCount = () => {
            return questionBlocks.reduce((count, block) => {
                if (!(block instanceof HTMLElement)) return count;

                const questionId = block.dataset.questionId;
                if (!questionId) return count;

                const checked = examRoot.querySelector(`input[name="answers[${questionId}]"]:checked`);
                return checked ? count + 1 : count;
            }, 0);
        };

        const updateProgress = () => {
            const answered = getAnsweredCount();
            const percent = totalQuestions > 0 ? Math.round((answered / totalQuestions) * 100) : 0;

            if (progressLabel instanceof HTMLElement) {
                progressLabel.textContent = `${answered} / ${totalQuestions} đã trả lời`;
            }

            if (progressFill instanceof HTMLElement) {
                progressFill.style.width = `${percent}%`;
            }

            navButtons.forEach((button) => {
                if (!(button instanceof HTMLButtonElement)) return;

                const questionId = button.dataset.navQuestion;
                if (!questionId) return;

                const answeredInput = examRoot.querySelector(`input[name="answers[${questionId}]"]:checked`);
                button.classList.toggle("done", !!answeredInput);
            });
        };

        navButtons.forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) return;

            button.addEventListener("click", () => {
                const targetId = button.dataset.navQuestion;
                if (!targetId) return;

                const target = examRoot.querySelector(`[data-question-id="${targetId}"]`);
                if (!(target instanceof HTMLElement)) return;

                target.scrollIntoView({ behavior: "smooth", block: "start" });
                navButtons.forEach((nav) => nav.classList.remove("current"));
                button.classList.add("current");
            });
        });

        examRoot.addEventListener("change", (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) return;
            if (target.type !== "radio") return;
            if (!target.name || !target.name.startsWith("answers[")) return;
            updateProgress();
        });

        form.addEventListener("submit", () => {
            submitted = true;
        });

        window.addEventListener("beforeunload", (event) => {
            if (submitted) return;
            if (getAnsweredCount() === 0) return;
            event.preventDefault();
            event.returnValue = "";
        });

        const minutesAttr = examRoot.dataset.examMinutes;
        const minutes = Number.parseInt(minutesAttr || "0", 10);

        if (timerEl instanceof HTMLElement && Number.isFinite(minutes) && minutes > 0) {
            let remainingSeconds = minutes * 60;

            const renderTime = () => {
                const safeSeconds = Math.max(remainingSeconds, 0);
                const mm = String(Math.floor(safeSeconds / 60)).padStart(2, "0");
                const ss = String(safeSeconds % 60).padStart(2, "0");
                timerEl.textContent = `${mm}:${ss}`;
            };

            renderTime();

            const interval = window.setInterval(() => {
                remainingSeconds -= 1;
                renderTime();

                if (remainingSeconds > 0) return;

                window.clearInterval(interval);
                submitted = true;
                form.submit();
            }, 1000);
        }

        updateProgress();
    };

    const setupPreviewQuestionPayload = () => {
        const form = document.querySelector("form[data-preview-form]");
        if (!(form instanceof HTMLFormElement)) return;

        const payloadInput = form.querySelector("[data-preview-payload]");
        if (!(payloadInput instanceof HTMLInputElement)) return;

        const questionList = form.querySelector("[data-preview-question-list]");
        if (!(questionList instanceof HTMLElement)) return;

        const addQuestionButton = form.querySelector("[data-add-question]");
        const shuffleAnswersButton = form.querySelector("[data-shuffle-answers]");
        const optionLabels = ["A", "B", "C", "D"];

        const toCardList = () => {
            return Array.from(questionList.querySelectorAll("[data-preview-question]"))
                .filter((card) => card instanceof HTMLElement);
        };

        const buildShuffledLabels = () => {
            const labels = [...optionLabels];

            for (let index = labels.length - 1; index > 0; index -= 1) {
                const randomIndex = Math.floor(Math.random() * (index + 1));
                [labels[index], labels[randomIndex]] = [labels[randomIndex], labels[index]];
            }

            return labels;
        };

        const shuffleAnswersInCard = (card) => {
            if (!(card instanceof HTMLElement)) return;

            const answerInputs = {};
            const radioInputs = {};

            optionLabels.forEach((label) => {
                answerInputs[label] = card.querySelector(`[data-field="answer"][data-option="${label}"]`);
                radioInputs[label] = card.querySelector(`input[data-field="correct_answer"][value="${label}"]`);
            });

            const allInputsReady = optionLabels.every((label) => (
                answerInputs[label] instanceof HTMLTextAreaElement
                && radioInputs[label] instanceof HTMLInputElement
            ));

            if (!allInputsReady) return;

            const originalAnswers = {};
            optionLabels.forEach((label) => {
                const input = answerInputs[label];
                originalAnswers[label] = input instanceof HTMLTextAreaElement ? (input.value || "") : "";
            });

            const checkedRadio = card.querySelector('input[data-field="correct_answer"]:checked');
            const checkedValue = checkedRadio instanceof HTMLInputElement
                ? (checkedRadio.value || "").toUpperCase()
                : "A";
            const originalCorrect = optionLabels.includes(checkedValue) ? checkedValue : "A";

            let shuffledLabels = buildShuffledLabels();
            let attempts = 0;
            while (
                attempts < 5
                && shuffledLabels.every((label, index) => label === optionLabels[index])
            ) {
                shuffledLabels = buildShuffledLabels();
                attempts += 1;
            }

            let nextCorrect = originalCorrect;

            optionLabels.forEach((targetLabel, index) => {
                const sourceLabel = shuffledLabels[index];
                const answerInput = answerInputs[targetLabel];

                if (answerInput instanceof HTMLTextAreaElement) {
                    answerInput.value = originalAnswers[sourceLabel] || "";
                }

                if (sourceLabel === originalCorrect) {
                    nextCorrect = targetLabel;
                }
            });

            const nextCorrectRadio = radioInputs[nextCorrect];
            if (nextCorrectRadio instanceof HTMLInputElement) {
                nextCorrectRadio.checked = true;
            }
        };

        const setCardInputNames = (card, index) => {
            if (!(card instanceof HTMLElement)) return;

            const questionTextarea = card.querySelector('textarea[data-field="question_content"]');
            if (questionTextarea instanceof HTMLTextAreaElement) {
                questionTextarea.name = `questions[${index}][question_content]`;
            }

            const radios = Array.from(card.querySelectorAll('input[data-field="correct_answer"]'));
            radios.forEach((radio) => {
                if (!(radio instanceof HTMLInputElement)) return;
                radio.name = `questions[${index}][correct_answer]`;
            });

            optionLabels.forEach((label) => {
                const answerInput = card.querySelector(`[data-field="answer"][data-option="${label}"]`);
                if (!(answerInput instanceof HTMLTextAreaElement)) return;
                answerInput.name = `questions[${index}][answers][${label}]`;
            });
        };

        const reindexQuestionCards = () => {
            const cards = toCardList();
            cards.forEach((card, index) => {
                const numberEl = card.querySelector("[data-question-number]");
                if (numberEl instanceof HTMLElement) {
                    numberEl.textContent = String(index + 1).padStart(2, "0");
                }
                setCardInputNames(card, index);
            });
        };

        const buildQuestionCard = (index) => {
            const article = document.createElement("article");
            article.className = "card ai-question-card";
            article.setAttribute("data-evidence-quote", "");
            article.setAttribute("data-reasoning", "");
            article.setAttribute("data-explanation", "");
            article.setAttribute("data-confidence-score", "0");
            article.setAttribute("data-grounding-status", "unknown");

            article.innerHTML = `
                <div class="ai-question-head">
                    <p><strong data-question-number>${String(index + 1).padStart(2, "0")}</strong> | TRẮC NGHIỆM</p>
                    <div class="ai-question-tools">
                        <span class="badge">1 điểm</span>
                        <button type="button" class="btn ghost small danger-outline" data-remove-question>Xóa câu hỏi</button>
                    </div>
                </div>

                <label class="ai-question-field">
                    Câu hỏi
                    <div data-rich-field class="rich-field-stack">
                        <textarea rows="4" required data-preview-input="1" data-field="question_content" data-rich-paste="1" data-question-live-preview="1"></textarea>
                        <div class="quiz-question-live-preview" data-question-img-preview hidden>
                            <p class="muted" style="margin:0 0 8px;">Xem trước ảnh (khi lưu, ảnh sẽ hiện trong đề)</p>
                            <div data-question-img-preview-body class="quiz-rich-field"></div>
                        </div>
                        <div class="rich-image-row">
                            <button type="button" class="btn ghost small" data-rich-image-pick>Chèn ảnh</button>
                            <span class="muted rich-image-hint">Ctrl+V · kéo thả</span>
                            <input type="file" accept="image/*" hidden data-rich-image-input="1" aria-hidden="true">
                        </div>
                    </div>
                </label>

                <div class="ai-option-grid">
                    <label class="ai-option-row">
                        <input type="radio" value="A" checked data-preview-input="1" data-field="correct_answer">
                        <span class="mono">A.</span>
                        <textarea rows="2" required data-preview-input="1" data-field="answer" data-option="A"></textarea>
                    </label>
                    <label class="ai-option-row">
                        <input type="radio" value="B" data-preview-input="1" data-field="correct_answer">
                        <span class="mono">B.</span>
                        <textarea rows="2" required data-preview-input="1" data-field="answer" data-option="B"></textarea>
                    </label>
                    <label class="ai-option-row">
                        <input type="radio" value="C" data-preview-input="1" data-field="correct_answer">
                        <span class="mono">C.</span>
                        <textarea rows="2" required data-preview-input="1" data-field="answer" data-option="C"></textarea>
                    </label>
                    <label class="ai-option-row">
                        <input type="radio" value="D" data-preview-input="1" data-field="correct_answer">
                        <span class="mono">D.</span>
                        <textarea rows="2" required data-preview-input="1" data-field="answer" data-option="D"></textarea>
                    </label>
                </div>
            `;

            setCardInputNames(article, index);

            return article;
        };

        if (addQuestionButton instanceof HTMLButtonElement) {
            addQuestionButton.addEventListener("click", () => {
                const nextIndex = toCardList().length;
                const newCard = buildQuestionCard(nextIndex);
                questionList.appendChild(newCard);
                reindexQuestionCards();

                const questionInput = newCard.querySelector('textarea[data-field="question_content"]');
                if (questionInput instanceof HTMLTextAreaElement) {
                    questionInput.focus();
                    renderQuestionImagePreview(questionInput);
                }
            });
        }

        if (shuffleAnswersButton instanceof HTMLButtonElement) {
            shuffleAnswersButton.addEventListener("click", () => {
                toCardList().forEach((card) => {
                    shuffleAnswersInCard(card);
                });
            });
        }

        questionList.addEventListener("click", (event) => {
            const rawTarget = event.target;
            if (!(rawTarget instanceof HTMLElement)) return;

            const removeButton = rawTarget.closest("[data-remove-question]");
            if (!(removeButton instanceof HTMLButtonElement)) return;

            const card = removeButton.closest("[data-preview-question]");
            if (!(card instanceof HTMLElement)) return;

            card.remove();
            reindexQuestionCards();
        });

        reindexQuestionCards();
        refreshAllQuestionPreviews();

        form.addEventListener("submit", () => {
            const questionCards = toCardList();
            if (questionCards.length === 0) {
                payloadInput.value = "[]";
                return;
            }

            const questions = questionCards
                .map((card) => {
                    if (!(card instanceof HTMLElement)) return null;

                    const questionTextarea = card.querySelector('textarea[data-field="question_content"]');
                    const correctInput = card.querySelector('input[data-field="correct_answer"]:checked');
                    const answerA = card.querySelector('[data-field="answer"][data-option="A"]');
                    const answerB = card.querySelector('[data-field="answer"][data-option="B"]');
                    const answerC = card.querySelector('[data-field="answer"][data-option="C"]');
                    const answerD = card.querySelector('[data-field="answer"][data-option="D"]');

                    if (!(questionTextarea instanceof HTMLTextAreaElement)) return null;
                    if (!(answerA instanceof HTMLTextAreaElement)) return null;
                    if (!(answerB instanceof HTMLTextAreaElement)) return null;
                    if (!(answerC instanceof HTMLTextAreaElement)) return null;
                    if (!(answerD instanceof HTMLTextAreaElement)) return null;

                    let correctAnswer = "A";
                    if (correctInput instanceof HTMLInputElement) {
                        const normalized = (correctInput.value || "").toUpperCase();
                        if (["A", "B", "C", "D"].includes(normalized)) {
                            correctAnswer = normalized;
                        }
                    }

                    const sourceRaw = (card.getAttribute("data-source") || "extract").toLowerCase();
                    const source = ["ai", "extract", "manual"].includes(sourceRaw) ? sourceRaw : "extract";
                    const evidenceQuote = card.getAttribute("data-evidence-quote") || "";
                    const reasoning = card.getAttribute("data-reasoning") || "";
                    const explanation = card.getAttribute("data-explanation") || "";
                    const confidenceScore = parseInt(card.getAttribute("data-confidence-score") || "0", 10);
                    const groundingStatus = card.getAttribute("data-grounding-status") || "unknown";

                    return {
                        question_content: questionTextarea.value || "",
                        answers: {
                            A: answerA.value || "",
                            B: answerB.value || "",
                            C: answerC.value || "",
                            D: answerD.value || "",
                        },
                        correct_answer: correctAnswer,
                        source,
                        evidence_quote: evidenceQuote,
                        reasoning: reasoning,
                        explanation: explanation,
                        confidence_score: confidenceScore,
                        grounding_status: groundingStatus,
                    };
                })
                .filter((item) => item !== null);

            payloadInput.value = JSON.stringify(questions);

            form.querySelectorAll("[data-preview-input='1']").forEach((field) => {
                if (
                    field instanceof HTMLInputElement
                    || field instanceof HTMLTextAreaElement
                    || field instanceof HTMLSelectElement
                ) {
                    field.removeAttribute("name");
                }
            });
        });
    };

    const setupLandingJoinQuiz = () => {
        const form = document.querySelector("[data-join-quiz-form]");
        if (!(form instanceof HTMLFormElement)) return;

        const input = form.querySelector("[data-join-quiz-input]");
        if (!(input instanceof HTMLInputElement)) return;

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const raw = (input.value || "").trim();
            if (raw === "") {
                input.focus();
                return;
            }

            const isAbsoluteUrl = /^https?:\/\//i.test(raw);
            if (isAbsoluteUrl) {
                window.location.href = raw;
                return;
            }

            const startsWithSlash = raw.startsWith("/");
            if (startsWithSlash) {
                window.location.href = raw;
                return;
            }

            if (/^\d+$/.test(raw)) {
                window.location.href = `/quizzes/${raw}/take`;
                return;
            }

            window.location.href = `/q/${encodeURIComponent(raw)}`;
        });
    };

    const setupQuizCreateTabs = () => {
        const root = document.querySelector("[data-create-tabs]");
        if (!(root instanceof HTMLElement)) return;

        const triggers = Array.from(root.querySelectorAll("[data-create-tab-trigger]"))
            .filter((el) => el instanceof HTMLButtonElement);
        const panels = Array.from(document.querySelectorAll("[data-create-tab-panel]"))
            .filter((el) => el instanceof HTMLElement);

        if (triggers.length === 0 || panels.length === 0) return;

        const activate = (name) => {
            triggers.forEach((btn) => {
                const active = btn.dataset.createTabTrigger === name;
                btn.classList.toggle("is-active", active);
                btn.setAttribute("aria-selected", active ? "true" : "false");
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.createTabPanel !== name;
            });
        };

        triggers.forEach((btn) => {
            btn.addEventListener("click", () => {
                activate(btn.dataset.createTabTrigger || "import");
            });
        });

        const initial = triggers.find((btn) => btn.classList.contains("is-active"))?.dataset.createTabTrigger || "import";
        activate(initial);
    };

    const setupEvidenceHighlighting = () => {
        const viewer = document.getElementById("document-viewer-content");
        if (!viewer) return;

        const originalHtml = viewer.innerHTML;

        document.addEventListener("focusin", (e) => {
            const target = e.target;
            if (!(target instanceof HTMLElement)) return;

            const card = target.closest("[data-preview-question]");
            if (!card) return;

            const quote = card.getAttribute("data-evidence-quote");
            if (!quote || quote.trim() === "") {
                viewer.innerHTML = originalHtml;
                return;
            }

            const safeQuote = escapeHtmlPreview(quote);
            
            if (originalHtml.includes(safeQuote)) {
                viewer.innerHTML = originalHtml.replace(
                    safeQuote, 
                    `<mark class="evidence-highlight" style="background-color: #fef08a; padding: 2px 4px; border-radius: 4px; font-weight: bold; box-shadow: 0 0 0 2px #eab308; transition: all 0.3s ease;">${safeQuote}</mark>`
                );
                
                const mark = viewer.querySelector("mark.evidence-highlight");
                if (mark) {
                    const panel = viewer.closest(".preview-document-panel");
                    if (panel) {
                        const panelRect = panel.getBoundingClientRect();
                        const markRect = mark.getBoundingClientRect();
                        const offset = markRect.top - panelRect.top - (panelRect.height / 2) + (markRect.height / 2);
                        panel.scrollTo({
                            top: panel.scrollTop + offset,
                            behavior: "smooth"
                        });
                    }
                }
            } else {
                viewer.innerHTML = originalHtml;
            }
        });
    };

    setupPreviewQuestionPayload();
    setupAiQuizGenerationLoading();
    setupFormSubmitLock();
    setupPasswordToggles();
    setupFileInputFeedback();
    setupTableExpandRows();
    setupExamExperience();
    setupLandingJoinQuiz();
    setupQuizCreateTabs();
    setupEvidenceHighlighting();

    const setupAdminUserFilter = () => {
        const input = document.querySelector("[data-admin-user-filter]");
        if (!(input instanceof HTMLInputElement)) return;

        input.addEventListener("input", () => {
            const q = (input.value || "").trim().toLowerCase();
            document.querySelectorAll("[data-admin-user-row]").forEach((row) => {
                if (!(row instanceof HTMLElement)) return;
                const hay = (row.getAttribute("data-search") || "").toLowerCase();
                row.style.display = q === "" || hay.includes(q) ? "" : "none";
            });
        });
    };

    setupAdminUserFilter();
})();
