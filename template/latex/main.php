<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. Neither the name of the author nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

function TPL_latex__core__main($t, $id, $d, $so)
{
	header('Content-Type: text/plain; encoding=UTF-8');
	echo	"%\n",
		"%  ", isset($so['page_title_format'])
				? sprintf($so['page_title_format'], @$so['page_title'])
				: @$so['page_title'], "\n",
		"%\n",
		"\\documentclass[12pt,english]{book}\n",
		"\\usepackage{ae,aecompl}\n",
		"\\usepackage{avant}\n",
		"\\renewcommand{\\ttdefault}{lmtt}\n",
		"\\renewcommand{\\familydefault}{\\rmdefault}\n",
		"\\usepackage[T1]{fontenc}\n",
		"\\usepackage[utf8]{inputenc}\n",
		"\\usepackage[a4paper]{geometry}\n",
		"\\geometry{verbose,tmargin=28mm,bmargin=32mm,lmargin=35mm,rmargin=20mm,headheight=14.5pt}\n",
		"\\usepackage{babel}\n",
		"\\usepackage{graphicx}\n",
		"\\usepackage{color}\n",
		"\\usepackage{tabularx}\n",
		"\\usepackage{fancyhdr}\n",
		"\\pagestyle{fancy}\n",
		"\\renewcommand{\headrulewidth}{0.2pt}\n",
		"\\renewcommand{\\footnoterule}{\\noindent\\rule{15em}{0.2pt}\\vspace{2pt}}\n",
		"\\fancyhead[RE,LO]{}\n",
		"\\fancyhead[LE,RO]{{\sc\leftmark}}\n",
		"\\renewcommand{\chaptermark}[1]{\markboth{\\thechapter\ #1}{}}\n",
		"\\renewcommand{\sectionmark}[1]{\markright{\\thesection\ #1}}\n",
		"\n",
		"% shrink too big images automagicaly\n",
		"\\newsavebox\\IBox\n",
		"\\let\\Includegrfx\\includegraphics\n",
		"\\renewcommand\\includegraphics[2][]{%\n",
		"\\sbox\\IBox{\\Includegrfx[#1]{#2}}%\n",
		"\\ifdim\\wd\\IBox>\\textwidth\\resizebox{\\textwidth}{!}{\\usebox\\IBox}\\else\n",
		"\\usebox\\IBox\\fi}\n",
		"\n";

	if (DEVELOPMENT_ENVIRONMENT) {
		echo	"% todo and fixme marks\n",
			"\\definecolor{TODOcolor}{RGB}{160, 0, 0}\n",
			"\\definecolor{TODO_TOCcolor}{RGB}{160, 64, 64}\n",
			"\\newcommand{\\TODO}[1]{\n",
			"	\\addtocontents{toc}{%\n",
			"	\\noindent \\leftskip 9em \\rightskip 3em\n",
			"	{\\scriptsize\\color{TODO_TOCcolor} {\\bf TODO:} {\\it #1}}\n",
			"	\\par}\n",
			"	\\par\\medskip\\noindent\\hangindent5em\n",
			"	\\makebox[5em][l]{\\bf\\textcolor{TODOcolor}{TODO:}}{\\it #1}\\medskip}\n",
			"\\newcommand{\\FIXME}[1]{\n",
			"	\\addtocontents{toc}{%\n",
			"	\\noindent \\leftskip 9em \\rightskip 3em\n",
			"	{\\scriptsize\\color{TODO_TOCcolor} {\\bf FIXME:} {\\it #1}}\n",
			"	\\par}\n",
			"	\\par\\medskip\\noindent\\hangindent5em\n",
			"	\\makebox[5em][l]{\\bf\\textcolor{TODOcolor}{FIXME:}}{\\it #1}\\medskip}\\par\n",
			"\n";
	}

	$t->process_slot('latex_preamble');

	echo	"\n\begin{document}\n";

	$t->process_slot('latex_body');
	$t->process_slot('default');	// fallback

	echo	"\n\end{document}\n";
}

